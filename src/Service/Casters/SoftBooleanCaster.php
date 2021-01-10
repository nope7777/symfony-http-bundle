<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\Casters;

final class SoftBooleanCaster implements SoftCasterInterface
{
    private const STRING_TRUE = 'true';
    private const STRING_FALSE = 'false';

    public function cast($value)
    {
        if (! is_scalar($value)) {
            return $value;
        }

        if ($value === self::STRING_TRUE) {
            return true;
        }

        if ($value === self::STRING_FALSE) {
            return false;
        }

        return $value;
    }
}
