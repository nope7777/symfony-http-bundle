<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Interfaces;

use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ValidationExceptionInterface
{
    public function getViolationList(): ConstraintViolationListInterface;
    public function getClass(): string;
    public function getPayload(): array;
}