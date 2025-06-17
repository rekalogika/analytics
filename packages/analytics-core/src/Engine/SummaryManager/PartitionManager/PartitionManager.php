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

namespace Rekalogika\Analytics\Engine\SummaryManager\PartitionManager;

use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\PartitionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
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

    public function getLowestPartitionFromEntity(object $entity): Partition
    {
        $source = $this->partitionMetadata->getSource();

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
        $valueResolver = $this->partitionMetadata->getSource();
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
