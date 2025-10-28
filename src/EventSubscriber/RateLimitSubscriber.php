<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\RateLimitExceededException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitSubscriber implements EventSubscriberInterface
{
    private array $limiters = [];

    public function __construct(
        private readonly RateLimiterFactory $apiGlobalLimiter,
        private readonly RateLimiterFactory $apiReadLimiter,
        private readonly RateLimiterFactory $apiWriteLimiter,
        private readonly RateLimiterFactory $apiCartModifyLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 15],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
            KernelEvents::EXCEPTION => ['onKernelException', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Only apply rate limiting to API routes
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        $clientIp = $request->getClientIp() ?? 'unknown';
        $method = $request->getMethod();

        // Apply global API rate limit
        $globalLimiter = $this->apiGlobalLimiter->create($clientIp);
        $globalLimit = $globalLimiter->consume(1);
        $this->limiters['global'] = $globalLimit;

        if (!$globalLimit->isAccepted()) {
            throw new RateLimitExceededException(
                'API rate limit exceeded. Please try again later.'
            );
        }

        // Apply read/write specific limits
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            $readLimiter = $this->apiReadLimiter->create($clientIp);
            $readLimit = $readLimiter->consume(1);
            $this->limiters['read'] = $readLimit;

            if (!$readLimit->isAccepted()) {
                throw new RateLimitExceededException(
                    'Read API rate limit exceeded. Please try again later.'
                );
            }
        } else {
            $writeLimiter = $this->apiWriteLimiter->create($clientIp);
            $writeLimit = $writeLimiter->consume(1);
            $this->limiters['write'] = $writeLimit;

            if (!$writeLimit->isAccepted()) {
                throw new RateLimitExceededException(
                    'Write API rate limit exceeded. Please try again later.'
                );
            }
        }

        // Apply cart modification specific limit for cart item operations
        if (
            preg_match('#^/api/carts/[^/]+/items#', $path)
            && in_array($method, ['POST', 'PATCH', 'DELETE'])
        ) {
            $cartModifyLimiter = $this->apiCartModifyLimiter->create($clientIp);
            $cartModifyLimit = $cartModifyLimiter->consume(1);
            $this->limiters['cart_modify'] = $cartModifyLimit;

            if (!$cartModifyLimit->isAccepted()) {
                throw new RateLimitExceededException(
                    'Cart modification rate limit exceeded. Please slow down.'
                );
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || empty($this->limiters)) {
            return;
        }

        $response = $event->getResponse();

        // Add rate limit headers from the most restrictive limiter
        foreach ($this->limiters as $limiter) {
            $response->headers->set(
                'X-RateLimit-Remaining',
                (string) $limiter->getRemainingTokens()
            );
            $response->headers->set(
                'X-RateLimit-Limit',
                (string) $limiter->getLimit()
            );

            if ($limiter->getRetryAfter() !== null) {
                $response->headers->set(
                    'X-RateLimit-Reset',
                    (string) $limiter->getRetryAfter()->getTimestamp()
                );
                $response->headers->set(
                    'Retry-After',
                    (string) $limiter->getRetryAfter()->getTimestamp()
                );
            }

            // Only set headers from first (most restrictive) limiter
            break;
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof RateLimitExceededException) {
            $response = new JsonResponse([
                'error' => [
                    'message' => $exception->getMessage(),
                    'code' => 'RATE_LIMIT_EXCEEDED',
                ],
            ], 429);

            // Add rate limit headers if available
            if (!empty($this->limiters)) {
                foreach ($this->limiters as $limiter) {
                    $response->headers->set(
                        'X-RateLimit-Remaining',
                        (string) $limiter->getRemainingTokens()
                    );
                    $response->headers->set(
                        'X-RateLimit-Limit',
                        (string) $limiter->getLimit()
                    );

                    if ($limiter->getRetryAfter() !== null) {
                        $response->headers->set(
                            'X-RateLimit-Reset',
                            (string) $limiter->getRetryAfter()->getTimestamp()
                        );
                        $response->headers->set(
                            'Retry-After',
                            (string) $limiter->getRetryAfter()->getTimestamp()
                        );
                    }
                    break;
                }
            }

            $event->setResponse($response);
        }
    }
}
