<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\Casters;

final class SoftBooleanCaster implements SoftCasterInterface
{
    private const TRUE_VALUES = ['true', '1'];
    private const FALSE_VALUES = ['false', '0'];

    public function cast($value)
    {
        if (! is_scalar($value)) {
            return $value;
        }

        if (in_array($value, self::TRUE_VALUES)) {
            return true;
        }

        if (in_array($value, self::FALSE_VALUES)) {
            return false;
        }

        return $value;
    }
}
