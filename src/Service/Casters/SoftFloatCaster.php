<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\Casters;

final class SoftFloatCaster implements SoftCasterInterface
{
    public function cast($value)
    {
        if (! is_scalar($value)) {
            return $value;
        }

        return is_numeric($value) ? (float) $value : $value;
    }
}
