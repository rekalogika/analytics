<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/analytics package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Analytics\Engine\Handler;

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\PartitionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Manages the partitions of a specific summary class.
 */
final readonly class PartitionHandler
{
    private PartitionMetadata $partitionMetadata;

    public function __construct(
        SummaryMetadata $metadata,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->partitionMetadata = $metadata->getPartition();
    }

    private function getSourceProperty(): string
    {
        $source = $this->partitionMetadata->getSource();

        $sourceProperty = $source->getInvolvedProperties()[0]
            ?? throw new UnexpectedValueException(\sprintf(
                'Partition source "%s" does not have any involved properties',
                get_debug_type($source),
            ));

        return $sourceProperty;
    }

    /**
     * Gets the partitioning key from the entity, takes its value, then
     * determines the lowest partition from the value.
     */
    public function getLowestPartitionFromEntity(object $entity): Partition
    {
        return $this->tryGetLowestPartitionFromEntity($entity)
            ?? throw new UnexpectedValueException(
                'Partition source value must not be null',
            );
    }

    /**
     * Same as `getLowestPartitionFromEntity()`, but returns null if the
     * partitioning key of the entity is null, e.g. an identity column of an
     * entity that is not yet inserted.
     */
    public function tryGetLowestPartitionFromEntity(object $entity): ?Partition
    {
        /** @psalm-suppress MixedAssignment */
        $sourceValue = $this->propertyAccessor->getValue($entity, $this->getSourceProperty());

        return $this->tryCreateLowestPartitionFromSourceValue($sourceValue);
    }

    /**
     * Determines the lowest partition from the value the partitioning key of
     * an entity had before it was modified. Returns null if the partitioning
     * key is not modified, or if its previous value is null.
     *
     * @param array<string,mixed> $originalValues The values of the modified
     * properties of the entity before the modification, keyed by the property
     * name.
     */
    public function tryGetOriginalLowestPartition(array $originalValues): ?Partition
    {
        // the source property can be a property path, e.g. `customer.id`, in
        // which case the modified property is the first element of the path

        $path = explode('.', $this->getSourceProperty(), 2);

        if (!\array_key_exists($path[0], $originalValues)) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $sourceValue = $originalValues[$path[0]];

        if (isset($path[1])) {
            if (!\is_object($sourceValue) && !\is_array($sourceValue)) {
                return null;
            }

            /** @psalm-suppress MixedAssignment */
            $sourceValue = $this->propertyAccessor->getValue($sourceValue, $path[1]);
        }

        return $this->tryCreateLowestPartitionFromSourceValue($sourceValue);
    }

    private function tryCreateLowestPartitionFromSourceValue(
        mixed $sourceValue,
    ): ?Partition {
        if ($sourceValue === null) {
            return null;
        }

        // the value is not necessarily a string or an integer, it is up to
        // the value resolver to interpret it, e.g. DateToInteger takes dates

        if ($sourceValue instanceof \Stringable) {
            $sourceValue = $sourceValue->__toString();
        }

        return $this->createLowestPartitionFromSourceValue($sourceValue);
    }

    /**
     * Transforms a value of the partitioning key from the source into the
     * integer domain of the partitions, i.e. the domain of
     * `Partition::getLowerBound()` and `Partition::getUpperBound()`.
     */
    public function transformSourceValueToSummaryValue(
        mixed $sourceValue,
    ): int {
        return $this->partitionMetadata
            ->getSource()
            ->transformSourceValueToSummaryValue($sourceValue);
    }

    /**
     * Takes a value of the partitioning key from the source, then creates the
     * partition object from the value and the specified level.
     */
    public function createPartitionFromSourceValue(
        mixed $sourceValue,
        int $level,
    ): Partition {
        $partitionClass = $this->partitionMetadata->getPartitionClass();
        $inputValue = $this->transformSourceValueToSummaryValue($sourceValue);

        return $partitionClass::createFromSourceValue($inputValue, $level);
    }

    /**
     * Creates the lowest partition level from the source partitioning key
     * value.
     */
    public function createLowestPartitionFromSourceValue(
        mixed $sourceValue,
    ): Partition {
        $partitionClass = $this->partitionMetadata->getPartitionClass();
        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);

        return $this->createPartitionFromSourceValue($sourceValue, $lowestLevel);
    }

    /**
     * Creates the highest partition level from the source partitioning key
     * value.
     */
    public function createHighestPartitionFromSourceValue(
        mixed $sourceValue,
    ): Partition {
        $partitionClass = $this->partitionMetadata->getPartitionClass();
        $highestLevel = PartitionUtil::getHighestLevel($partitionClass);

        return $this->createPartitionFromSourceValue($sourceValue, $highestLevel);
    }

    public function getUpperBoundSourceValueFromPartition(
        Partition $partition,
    ): mixed {
        $inputBound = $partition->getUpperBound();
        $valueResolver = $this->partitionMetadata->getSource();

        return $valueResolver->transformSummaryValueToSourceValue($inputBound);
    }

    public function getLowerBoundSourceValueFromPartition(
        Partition $partition,
    ): mixed {
        $inputBound = $partition->getLowerBound();
        $valueResolver = $this->partitionMetadata->getSource();

        return $valueResolver->transformSummaryValueToSourceValue($inputBound);
    }

    public function getPartitionFromDirtyFlag(DirtyFlag $dirtyFlag): ?Partition
    {
        $partitionClass = $this->partitionMetadata->getPartitionClass();

        $key = $dirtyFlag->getKey();
        $level = $dirtyFlag->getLevel();

        if ($key === null || $level === null) {
            return null;
        }

        return $partitionClass::createFromSourceValue($key, $level);
    }
}
