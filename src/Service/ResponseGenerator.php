<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use N7\SymfonyHttpBundle\Response\ResponseEntityInterface;
use N7\SymfonyHttpBundle\Service\ResponseGenerator\MultiEntitiesResponsePayload;
use N7\SymfonyHttpBundle\Service\ResponseGenerator\MultiEntitiesResult;
use N7\SymfonyHttpBundle\Service\ResponseGenerator\Relation;
use N7\SymfonyHttpBundle\Service\ResponseGenerator\SingleEntityResult;
use ReflectionObject;
use RuntimeException;
use ArrayObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * S in SOLID stands for - Smultiple responsibilities.
 */
final class ResponseGenerator
{
    private const RELATIONS_LEVELS_DELIMITER = '.';

    private EntityManagerInterface $entityManager;
    private Serializer $serializer;

    private ArrayObject $relations;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->serializer = new Serializer([new PropertyNormalizer()], [new JsonEncoder()]);
    }

    public function getSingleEntityResponse(object $entity, string $dto, array $relations = []): JsonResponse
    {
        $this->relations = $this->initializeRelations($relations);
        ksort($relations);

        $this->handleEntitiesRelation([$entities], $relations);

        $payload = new SingleEntityResult(new $dto($entity), $this->relations);
        $payload = $this->serializer->serialize($payload, 'json', [
            PropertyNormalizer::PRESERVE_EMPTY_OBJECTS => true,
        ]);

        return new JsonResponse($payload, 200, [], true);
    }

    public function getMultipleEntitiesResponse(iterable $entities, string $dto, array $relations = []): JsonResponse
    {
        $this->relations = $this->initializeRelations($relations);
        ksort($relations);

        $this->handleEntitiesRelation($entities, $relations);

        $result = [];
        foreach ($entities as $entity) {
            $result[] = new $dto($entity);
        }

        $payload = new MultiEntitiesResponsePayload($result, $this->relations);
        $payload = $this->serializer->serialize($payload, 'json', [
            PropertyNormalizer::PRESERVE_EMPTY_OBJECTS => true,
        ]);

        return new JsonResponse($payload, 200, [], true);
    }

    private function handleEntitiesRelation(iterable $entities, array $relations = []): void
    {
        if (! $entities) {
            return;
        }

        $class = $this->getEntitiesClass($entities);

        foreach ($relations as $relationName => $dto) {
            if ($this->isCurrentLevelRelation($relationName)) {
                $relation = $this->getEntityRelation($class, $relationName);

                $related = $this->getRelatedEntities($entities, $relation);

                $this->applyRelation($related, $dto);
            } else {
                $parts = explode(self::RELATIONS_LEVELS_DELIMITER, $relationName);

                $currentLevelRelation = array_shift($parts);
                $nestedRelations = implode(self::RELATIONS_LEVELS_DELIMITER, $parts);

                $relation = $this->getEntityRelation($class, $currentLevelRelation);

                $related = $this->getRelatedEntities($entities, $relation);

                $this->handleEntitiesRelation($related, [$nestedRelations => $dto]);
            }
        }
    }

    private function applyRelation(iterable $entities, string $dto): void
    {
        if (! $entities) {
            return;
        }

        $class = $this->getEntitiesClass($entities);
        $identifier = $this->getEntityIdentifierProperty($class);

        $result = [];
        foreach ($entities as $entity) {
            $id = $this->getObjectPropery($entity, $identifier);

            $result[$id] = new $dto($entity);
        }

        $relationName = $dto::getEntityName();

        $mergedResult = ((array) $this->relations[$relationName]) + $result;

        $this->relations[$relationName] = new ArrayObject($mergedResult);
    }

    private function getRelatedEntities(iterable $entities, Relation $relation): array
    {
        if (! $sourceIds = $this->getSourceIds($entities, $relation)) {
            return [];
        }

        /** @var QueryBuilder $builder */
        $builder = $this->entityManager->createQueryBuilder();

        $builder->select('target')->from($relation->getTargetEntity(), 'target');

        if ($relation->getType() === Relation::TYPE_MANY_TO_MANY) {
            $builder->leftJoin('target.' . $relation->getJoinRelation(), 'links')
                ->where('links.' . $relation->getSourceColumn() . ' IN (:ids)')
                ->setParameter('ids', $sourceIds);
        } else {
            $builder->where('target.' . $relation->getTargetColumn() . ' IN (:ids)')
                ->setParameter('ids', $sourceIds);
        }

        // todo: cache

        return $builder->getQuery()->getResult();
    }

    private function getSourceIds(iterable $entities, Relation $relation): array
    {
        $property = $this->getClassPropertyNameFromField($relation->getSourceEntity(), $relation->getSourceColumn());

        $values = [];
        foreach ($entities as $entity) {
            $values[] = $this->getObjectPropery($entity, $property);
        }

        $values = array_unique($values);
        $values = array_values($values);

        return $values;
    }

    /**
     * Of cource there should be a better way.
     *
     * @param object $object
     * @param string $property
     * @return int|string
     */
    private function getObjectPropery(object $object, string $property)
    {
        $reflection = ClassUtils::newReflectionObject($object);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($object);
    }

    private function getClassPropertyNameFromField(string $class, string $field): string
    {
        return $this->entityManager->getClassMetadata($class)->getFieldName($field);
    }

    private function getEntityRelation(string $class, string $relation): Relation
    {
        return new Relation($this->entityManager, $class, $relation);
    }

    private function getEntityIdentifierProperty(string $class): string
    {
        $identifiers = $this->entityManager->getClassMetadata($class)->getIdentifierFieldNames();

        foreach ($identifiers as $identifier) {
            return $identifier;
        }

        throw new RuntimeException('Can\'t get class "' . $class . '" identifier');
    }

    private function initializeRelations(array $relations): ArrayObject
    {
        $result = [];

        foreach ($relations as $dto) {
            $result[$dto::getEntityName()] = new ArrayObject();
        }

        return new ArrayObject($result);
    }

    private function isCurrentLevelRelation(string $relationName): bool
    {
        return strpos($relationName, self::RELATIONS_LEVELS_DELIMITER) === false;
    }

    private function getEntitiesClass(iterable $items): string
    {
        foreach ($items as $item) {
            return get_class($item);
        }

        throw new RuntimeException('Empty list provided');
    }
}
