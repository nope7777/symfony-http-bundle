<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\Casters;

final class SoftBooleanCaster implements SoftCasterInterface
{
    private const TRUE_VALUES = ['true', true, '1', 1];
    private const FALSE_VALUES = ['false', false, '0', 0];

    public function cast($value)
    {
        if (! is_scalar($value)) {
            return $value;
        }

        if (in_array($value, self::TRUE_VALUES, true)) {
            return true;
        }

        if (in_array($value, self::FALSE_VALUES, true)) {
            return false;
        }

        return $value;
    }
}
