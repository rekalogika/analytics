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

namespace Rekalogika\Analytics\SummaryManager\PartitionManager;

use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\Summary\PartitionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Model\Entity\DirtyFlag;
use Rekalogika\Analytics\Util\PartitionUtil;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class PartitionManager
{
    private PartitionMetadata $partitionMetadata;

    public function __construct(
        SummaryMetadata $metadata,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->partitionMetadata = $metadata->getPartition();
    }

    /**
     * @return PartitionValueResolver<mixed>
     */
    private function resolvePartitionSource(object $entity): PartitionValueResolver
    {
        $source = $this->partitionMetadata->getSource();

        $parents = class_parents($entity::class);

        if ($parents === false) {
            $parents = [];
        }

        $classes = [
            $entity::class,
            ...$parents,
        ];

        foreach ($classes as $class) {
            if (isset($source[$class])) {
                return $source[$class];
            }
        }

        throw new MetadataException(\sprintf(
            'Partition source not found for class "%s"',
            $entity::class,
        ));
    }

    public function getLowestPartitionFromEntity(object $entity): Partition
    {
        $source = $this->resolvePartitionSource($entity);

        $sourceProperty = $source->getInvolvedProperties()[0]
            ?? throw new UnexpectedValueException(\sprintf(
                'Partition source "%s" does not have any involved properties',
                get_debug_type($source),
            ));

        $sourceValue = $this->propertyAccessor->getValue($entity, $sourceProperty);

        if ($sourceValue instanceof \Stringable) {
            $sourceValue = $sourceValue->__toString();
        }

        if (!\is_string($sourceValue) && !\is_int($sourceValue)) {
            throw new UnexpectedValueException(\sprintf(
                'Partition source value must be a string or an integer, "%s" given',
                get_debug_type($sourceValue),
            ));
        }

        return $this->createLowestPartitionFromSourceValue($sourceValue);
    }

    public function createPartitionFromSourceValue(
        mixed $sourceValue,
        int $level,
    ): Partition {
        $partitionClass = $this->partitionMetadata->getPartitionClass();

        $source = $this->partitionMetadata->getSource();
        $valueResolver = reset($source);

        if ($valueResolver === false) {
            throw new UnexpectedValueException(\sprintf(
                'Value resolver not found for class "%s"',
                $partitionClass,
            ));
        }

        $inputValue = $valueResolver->transformSourceValueToSummaryValue($sourceValue);

        return $partitionClass::createFromSourceValue($inputValue, $level);
    }

    public function createLowestPartitionFromSourceValue(
        mixed $sourceValue,
    ): Partition {
        $partitionClass = $this->partitionMetadata->getPartitionClass();
        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);

        return $this->createPartitionFromSourceValue($sourceValue, $lowestLevel);
    }

    public function createHighestPartitionFromSourceValue(
        mixed $sourceValue,
    ): Partition {
        $partitionClass = $this->partitionMetadata->getPartitionClass();
        $highestLevel = PartitionUtil::getHighestLevel($partitionClass);

        return $this->createPartitionFromSourceValue($sourceValue, $highestLevel);
    }

    /**
     * @param 'lower'|'upper' $type
     */
    public function calculateSourceBoundValueFromPartition(
        Partition $partition,
        string $type,
    ): mixed {
        if ($type === 'upper') {
            $inputBound = $partition->getUpperBound();
        } else {
            $inputBound = $partition->getLowerBound();
        }

        $source = $this->partitionMetadata->getSource();

        $valueResolver = reset($source);

        if ($valueResolver === false) {
            throw new UnexpectedValueException(\sprintf(
                'Value resolver not found for class "%s"',
                $partition::class,
            ));
        }

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
