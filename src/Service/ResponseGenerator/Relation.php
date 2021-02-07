<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\Service\ResponseGenerator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

final class Relation
{
    public const TYPE_ONE_TO_ONE = ClassMetadataInfo::ONE_TO_ONE;
    public const TYPE_MANY_TO_ONE = ClassMetadataInfo::MANY_TO_ONE;
    public const TYPE_ONE_TO_MANY = ClassMetadataInfo::ONE_TO_MANY;
    public const TYPE_MANY_TO_MANY = ClassMetadataInfo::MANY_TO_MANY;

    private int $type;

    private string $sourceEntity;
    private string $targetEntity;
    private string $sourceColumn;
    private string $targetColumn;

    private ?string $joinRelation = null;

    public function __construct(EntityManagerInterface $entityManager, string $class, string $relation)
    {
        $sourceMeta = $entityManager->getClassMetadata($class);
        $sourceRelation = $sourceMeta->getAssociationMapping($relation);

        $targetMeta = $entityManager->getClassMetadata($sourceRelation['targetEntity']);

        $this->type = $sourceRelation['type'];
        $this->sourceEntity = $sourceRelation['sourceEntity'];
        $this->targetEntity = $sourceRelation['targetEntity'];

        // I will refactor this part... someday...
        if ($this->type === self::TYPE_MANY_TO_MANY) {
            if ($sourceRelation['mappedBy'] ?? false) {
                $targetRelation = $targetMeta->getAssociationMapping($sourceRelation['mappedBy']);

                $this->sourceColumn = $sourceMeta->getFieldName($targetRelation['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);
                $this->targetColumn = $targetMeta->getFieldName($targetRelation['joinTable']['joinColumns'][0]['referencedColumnName']);

                $this->joinRelation = $sourceRelation['mappedBy'];
            } else {
                $this->sourceColumn = $sourceMeta->getFieldName($sourceRelation['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);
                $this->targetColumn = $targetMeta->getFieldName($sourceRelation['joinTable']['joinColumns'][0]['referencedColumnName']);

                $this->joinRelation = $sourceRelation['inversedBy'];
            }
        } else {
            if ($sourceRelation['mappedBy'] ?? false) {
                $targetRelation = $targetMeta->getAssociationMapping($sourceRelation['mappedBy']);

                $targetColumn = array_key_first($targetRelation['sourceToTargetKeyColumns']);
                $this->sourceColumn = $sourceMeta->getFieldName($targetRelation['sourceToTargetKeyColumns'][$targetColumn]);
                $this->targetColumn = $targetMeta->getFieldName($targetColumn);
            } else {
                $sourceColumn = array_key_first($sourceRelation['sourceToTargetKeyColumns']);
                $this->sourceColumn = $sourceMeta->getFieldName($sourceColumn);
                $this->targetColumn = $targetMeta->getFieldName($sourceRelation['sourceToTargetKeyColumns'][$sourceColumn]);
            }
        }
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getJoinRelation(): ?string
    {
        return $this->joinRelation;
    }

    public function getSourceEntity(): string
    {
        return $this->sourceEntity;
    }

    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    public function getSourceColumn()
    {
        return $this->sourceColumn;
    }

    public function getTargetColumn(): string
    {
        return $this->targetColumn;
    }
}
