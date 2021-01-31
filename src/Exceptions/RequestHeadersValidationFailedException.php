<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Exceptions;

use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

final class RequestHeadersValidationFailedException extends RuntimeException implements ValidationExceptionInterface
{
    private ConstraintViolationListInterface $violationList;
    private string $class;
    private array $payload;

    public function __construct(ConstraintViolationListInterface $violationList, string $class, array $headers)
    {
        parent::__construct('Request headers validation failed');

        $this->violationList = $violationList;
        $this->class = $class;
        $this->headers = $headers;
    }

    public function getViolationList(): ConstraintViolationListInterface
    {
        return $this->violationList;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getPayload(): array
    {
        return $this->headers;
    }
}
