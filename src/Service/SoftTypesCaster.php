<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service;

use N7\SymfonyValidatorsBundle\Validator\NestedObjects;
use Symfony\Component\Validator\Mapping\PropertyMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use N7\SymfonyHttpBundle\Service\Casters;

final class SoftTypesCaster
{
    private const SCALAR_INTEGER = 'int';
    private const SCALAR_FLOAT = 'float';
    private const SCALAR_BOOL = 'bool';
    private const SCALAR_BOOLEAN = 'boolean';
    private const SCALAR_STRING = 'string';

    private const TYPE_ARRAY = 'array';

    private const ARRAY_OF_INTEGER = 'int[]';
    private const ARRAY_OF_FLOAT = 'float[]';
    private const ARRAY_OF_BOOL = 'bool[]';
    private const ARRAY_OF_BOOLEAN = 'boolean[]';
    private const ARRAY_OF_STRING = 'string[]';
    private const AVAILABLE_ARRAYS_OF_TYPES = [
        self::ARRAY_OF_INTEGER,
        self::ARRAY_OF_FLOAT,
        self::ARRAY_OF_BOOL,
        self::ARRAY_OF_BOOLEAN,
        self::ARRAY_OF_STRING,
    ];

    private const ARRAY_OF_SCALARS_MAP = [
        self::ARRAY_OF_INTEGER => self::SCALAR_INTEGER,
        self::ARRAY_OF_FLOAT => self::SCALAR_FLOAT,
        self::ARRAY_OF_BOOL => self::SCALAR_BOOL,
        self::ARRAY_OF_BOOLEAN => self::SCALAR_BOOLEAN,
        self::ARRAY_OF_STRING => self::SCALAR_STRING,
    ];

    private ValidatorInterface $validator;

    /** @var Casters\SoftCasterInterface[] $casters */
    private array $casters = [
        self::SCALAR_INTEGER => Casters\SoftIntegerCaster::class,
        self::SCALAR_FLOAT => Casters\SoftFloatCaster::class,
        self::SCALAR_BOOL => Casters\SoftBooleanCaster::class,
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

    public function cast(string $class, array $payload, bool $defaultClassValuesToPayload = true): array
    {
        $meta = $this->validator->getMetadataFor($class);

        /** @var PropertyMetadata $propery */
        foreach ($meta->properties as $propery) {
            // Skipping, if property is not provided in payload
            if (! array_key_exists($propery->getName(), $payload)) {
                if ($defaultClassValuesToPayload) {
                    // If field is not presented in request but got default value in request class
                    // applying default value to this property
                    $payload = $this->applyDefaultValueToPayload($propery, $payload);
                }
                
                continue;
            }

            $payload[$propery->getName()] = $this->castProperty($payload[$propery->getName()], $propery);
        }

        return $payload;
    }

    private function applyDefaultValueToPayload(PropertyMetadata $propery, array $payload): array
    {
        $defaultValues = $propery->getReflectionMember($propery->class)
            ->getDeclaringClass()
            ->getDefaultProperties();

        if (array_key_exists($propery->getName(), $defaultValues)) {
            $payload[$propery->getName()] = $defaultValues[$propery->getName()];
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

        // Arrays casting
        if ($type === self::TYPE_ARRAY) {
            return $this->castArrayPropery($value, $propery);
        }

        // Nested objects casting
        if (class_exists($type) && is_array($value)) {
            return $this->cast($type, $value);
        }

        return $value;
    }

    private function castArrayPropery($value, PropertyMetadata $propery)
    {
        // Array of nested objects
        if ($nestedClass = $this->getNestedObjectClass($propery)) {
            return array_map(
                fn ($value) => $this->cast($nestedClass, $value),
                $value
            );
        }

        // Array of scalar types
        $arrayType = $this->getProperyTypeFromPhpDocAnnotations($propery);
        if (in_array($arrayType, self::AVAILABLE_ARRAYS_OF_TYPES, true) && is_array($value)) {
            $type = self::ARRAY_OF_SCALARS_MAP[$arrayType];

            return array_map(
                fn ($value) => $this->casters[$type]->cast($value),
                $value
            );
        }

        return $value;
    }

    private function getProperyTypeFromPhpDocAnnotations(PropertyMetadata $propery): ?string
    {
        if (! $docBlock = $propery->getReflectionMember($propery->class)->getDocComment()) {
            return null;
        }

        preg_match('/\@var ([^\n\s]+)/', $docBlock, $matches);

        return $matches[1] ?? null;
    }

    private function getNestedObjectClass(PropertyMetadata $propery): ?string
    {
        foreach ($propery->constraints as $constraint) {
            if ($constraint instanceof NestedObjects) {
                return $constraint->class;
            }
        }

        return null;
    }

    private function detectType(PropertyMetadata $property): ?string
    {
        if ($reflectionType = $property->getReflectionMember($property->class)->getType()) {
            return $reflectionType->getName();
        }

        return null;
    }
}
