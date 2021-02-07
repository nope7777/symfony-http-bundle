<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\ResponseGenerator;

use ArrayObject;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MultiEntitiesResponsePayload
{
    private array $entities;
    private ArrayObject $relations;

    public function __construct(array $entities, ArrayObject $relations)
    {
        $this->entities = $entities;
        $this->relations = $relations;
    }
}
