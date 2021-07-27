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
        
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : $value;
    }
}
