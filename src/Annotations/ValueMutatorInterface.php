<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Annotations;

/**
 * @Annotation
 */
interface ValueMutatorInterface
{
    /**
     * @param mixed $value
     * @param mixed $payload
     * @return mixed
     */
    public function mutate($value, array $payload);
}
