<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\ResponseGenerator;

use ArrayObject;

final class SingleEntityResponsePayload
{
    private object $entity;
    private ArrayObject $relations;

    public function __construct(object $entity, ArrayObject $relations)
    {
        $this->entity = $entity;
        $this->relations = $relations;
    }
}
