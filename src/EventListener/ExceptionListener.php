<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\CartItemNotFoundException;
use App\Exception\CartNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof CartNotFoundException,
            $exception instanceof CartItemNotFoundException => 404,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };

        $errorCode = match (true) {
            $exception instanceof CartNotFoundException => 'CART_NOT_FOUND',
            $exception instanceof CartItemNotFoundException => 'ITEM_NOT_FOUND',
            default => 'INTERNAL_SERVER_ERROR',
        };

        $response = new JsonResponse([
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $errorCode,
            ],
        ], $statusCode);

        $event->setResponse($response);
    }
}
