<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Listeners;

use N7\SymfonyHttpBundle\Interfaces\ValidationExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

final class RequestPayloadExceptionListener
{
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

        $event->setResponse(new JsonResponse([
            'code' => 400,
            'message' => 'Validation error occurred',
            'errors' => $errors,
        ], 400));
    }
}
