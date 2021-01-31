<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\EventListener;

use N7\SymfonyHttpBundle\Interfaces\ValidationExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

final class RequestPayloadExceptionListener
{
    private const RESPONSE_HTTP_CODE = 422;
    private const RESPONSE_MESSAGE = 'Validation error occurred';

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if (! $exception instanceof ValidationExceptionInterface) {
            return;
        }

        $errors = [];
        foreach ($exception->getViolationList() as $error) {
            $key = $error->getPropertyPath();
            $errors[$key] = $error->getMessage();
        }

        $response = new JsonResponse([
            'code' => self::RESPONSE_HTTP_CODE,
            'message' => self::RESPONSE_MESSAGE,
            'errors' => $errors,
        ], self::RESPONSE_HTTP_CODE);

        $event->setResponse($response);
    }
}
