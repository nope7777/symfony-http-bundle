<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\Casters;

final class SoftIntegerCaster implements SoftCasterInterface
{
    public function cast($value)
    {
        if (! is_scalar($value)) {
            return $value;
        }

        if (ctype_digit((string) $value)) {
            return (int) $value;
        }

        return $value;
    }
}
