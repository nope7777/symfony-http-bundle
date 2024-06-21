<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Interfaces;

interface RequestPropertyMapInterface
{
    /**
     * Returns an array of property names (key) and their corresponding names in the request (value).
     *
     * @return array<string, string>
     */
    public static function getPropertyMap(): array;
}
