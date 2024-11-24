<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service;

use N7\SymfonyHttpBundle\Annotations\ValueMutatorInterface;
use N7\SymfonyValidatorsBundle\Validator\NestedObject;
use N7\SymfonyValidatorsBundle\Validator\NestedObjects;
use Symfony\Component\Validator\Mapping\PropertyMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ReflectionClass;
use ReflectionProperty;

final class AnnotationsHandler
{
    public function apply(string $class, array $payload): array
    {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getProperties() as $propery) {
            if (! array_key_exists($propery->getName(), $payload)) {
                continue;
            }

            // Applying mutators
            $mutators = $this->getPropertyMutators($propery);
            foreach ($mutators as $mutator) {
                $payload[$propery->getName()] = $mutator->mutate(
                    $payload[$propery->getName()],
                    $payload,
                    $propery->getName()
                );
            }

            // If nested object
            if (
                ($nestedObject = $this->getPropertyNestedObjectClass($propery))
                && is_array($payload[$propery->getName()])
            ) {
                $payload[$propery->getName()] = $this->apply($nestedObject, $payload[$propery->getName()]);
            }

            // If array of objects
            if (
                ($nestedObjects = $this->getPropertyNestedObjectsClass($propery))
                && is_array($payload[$propery->getName()])
            ) {
                $payload[$propery->getName()] = array_map(
                    fn ($item) => is_array($item) ? $this->apply($nestedObjects, $item) : $item,
                    $payload[$propery->getName()]
                );
            }
        }

        return $payload;
    }

    /**
     * @param ReflectionProperty $property
     * @return ValueMutatorInterface[]
     */
    private function getPropertyMutators(ReflectionProperty $property): array
    {
        $mutators = $property->getAttributes(ValueMutatorInterface::class, \ReflectionAttribute::IS_INSTANCEOF);

        return array_map(fn (\ReflectionAttribute $attribute) => $attribute->newInstance(), $mutators);
    }

    private function getPropertyNestedObjectClass(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(NestedObject::class, \ReflectionAttribute::IS_INSTANCEOF);

        return $attributes ? $attributes[0]->newInstance()->class : null;
    }

    private function getPropertyNestedObjectsClass(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(NestedObjects::class, \ReflectionAttribute::IS_INSTANCEOF);

        return $attributes ? $attributes[0]->newInstance()->class : null;
    }
}
