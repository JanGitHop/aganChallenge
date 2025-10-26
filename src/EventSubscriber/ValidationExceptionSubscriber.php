<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ValidationExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle UnprocessableEntityHttpException from MapRequestPayload
        if ($exception instanceof UnprocessableEntityHttpException) {
            $previous = $exception->getPrevious();

            if ($previous instanceof ValidationFailedException) {
                $violations = $previous->getViolations();
                $details = [];
                $messages = [];
                $codes = [];

                foreach ($violations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $message = $violation->getMessage();

                    $details[$propertyPath] = [
                        'message' => $message,
                        'code' => 'INVALID_VALUE',
                    ];

                    $messages[] = $message;
                    $codes[] = strtoupper($propertyPath) . '_REQUIRED';
                }

                // For missing required fields, use simpler format
                if (count($messages) > 0 && str_contains($messages[0], 'required')) {
                    $response = new JsonResponse([
                        'error' => [
                            'message' => implode(' | ', $messages),
                            'code' => implode('|', $codes),
                        ],
                    ], 400);
                } else {
                    // For validation errors, include details
                    $response = new JsonResponse([
                        'error' => [
                            'message' => 'Validation failed',
                            'code' => 'VALIDATION_ERROR',
                            'details' => $details,
                        ],
                    ], 400);
                }

                $event->setResponse($response);
                return;
            }

            // Handle denormalization errors (type errors)
            if ($previous instanceof PartialDenormalizationException) {
                $details = [];

                foreach ($previous->getErrors() as $error) {
                    if ($error instanceof NotNormalizableValueException) {
                        $propertyPath = $error->getPath();
                        $expectedTypes = $error->getExpectedTypes();
                        $expectedType = !empty($expectedTypes) ? $expectedTypes[0] : 'unknown';

                        $details[$propertyPath] = [
                            'message' => sprintf(
                                'The type must be "%s", "%s" given.',
                                $expectedType,
                                get_debug_type($error->getCurrentType())
                            ),
                            'code' => 'INVALID_TYPE',
                        ];
                    }
                }

                $response = new JsonResponse([
                    'error' => [
                        'message' => 'Validation failed',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $details,
                    ],
                ], 400);

                $event->setResponse($response);
                return;
            }
        }

        // Handle BadRequestHttpException (e.g., malformed JSON)
        if ($exception instanceof BadRequestHttpException) {
            $response = new JsonResponse([
                'error' => [
                    'message' => $exception->getMessage() ?: 'Bad request',
                    'code' => 'BAD_REQUEST',
                ],
            ], 400);

            $event->setResponse($response);
        }
    }
}
