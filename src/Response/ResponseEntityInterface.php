<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Response;

interface ResponseEntityInterface
{
    public static function getEntityName(): string;
}
