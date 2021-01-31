<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\Casters;

interface SoftCasterInterface
{
    public function cast($value);
}
