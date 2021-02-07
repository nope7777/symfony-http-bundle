<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

final class MultipleEntitiesResponse extends JsonResponse
{
    public function __construct(iterable $entities, string $dto, array $relations = [])
    {


        parent::__construct($content);
    }
}
