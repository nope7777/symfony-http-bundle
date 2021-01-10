<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service;

use Symfony\Component\Validator\Mapping\PropertyMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use N7\SymfonyHttpBundle\Service\Casters;

final class SoftTypesCaster
{
    private const SCALAR_INTEGER = 'int';
    private const SCALAR_FLOAT = 'float';
    private const SCALAR_BOOLEAN = 'boolean';
    private const SCALAR_STRING = 'string';

    private ValidatorInterface $validator;

    /** @var Casters\SoftCasterInterface[] $casters */
    private array $casters = [
        self::SCALAR_INTEGER => Casters\SoftIntegerCaster::class,
        self::SCALAR_FLOAT => Casters\SoftFloatCaster::class,
        self::SCALAR_BOOLEAN => Casters\SoftBooleanCaster::class,
        self::SCALAR_STRING => Casters\SoftStringCaster::class,
    ];

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;

        $this->casters = array_map(
            fn (string $class): Casters\SoftCasterInterface => new $class,
            $this->casters
        );
    }

    public function cast(string $class, array $payload): array
    {
        $meta = $this->validator->getMetadataFor($class);

        /** @var PropertyMetadata $propery */
        foreach ($meta->properties as $propery) {
            // Skipping, if property is not provided in payload
            if (! array_key_exists($propery->getName(), $payload)) {
                continue;
            }

            $payload[$propery->getName()] = $this->castProperty($payload[$propery->getName()], $propery);
        }

        return $payload;
    }

    private function castProperty($value, PropertyMetadata $propery)
    {
        // If property type can't be detected
        if (! $type = $this->detectType($propery)) {
            return $value;
        }

        // Scalar types casting
        if (array_key_exists($type, $this->casters)) {
            return $this->casters[$type]->cast($value);
        }

        // TODO: If type === array
        // (array of scalar types)
        // (array of objects)
        // (keyed array)

        if (class_exists($type) && is_array($value)) {
            return $this->cast($type, $value);
        }

        return $value;
    }

    private function castArrayPropery($value, PropertyMetadata $propery)
    {

    }

    private function detectType(PropertyMetadata $property): ?string
    {
        if ($reflectionType = $property->getReflectionMember($property->class)->getType()) {
            return $reflectionType->getName();
        }

        return null;
    }
}
