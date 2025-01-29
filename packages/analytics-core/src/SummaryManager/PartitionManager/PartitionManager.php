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

use Rekalogika\Analytics\Metadata\PartitionMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Model\Entity\SummarySignal;
use Rekalogika\Analytics\Partition;
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

    public function getLowestPartitionFromEntity(object $entity): Partition
    {
        $source = $this->partitionMetadata->getSource()[$entity::class]
            ?? throw new \RuntimeException('Source not found');

        $sourceProperty = $source->getInvolvedProperties()[0]
            ?? throw new \UnexpectedValueException('Source property not found');

        $sourceValue = $this->propertyAccessor->getValue($entity, $sourceProperty);

        if ($sourceValue instanceof \Stringable) {
            $sourceValue = $sourceValue->__toString();
        }

        if (!\is_string($sourceValue) && !\is_int($sourceValue)) {
            throw new \UnexpectedValueException('Source value must be string or int');
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
            throw new \RuntimeException('Partition source is empty');
        }

        /** @var mixed */
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
    ): int|string {
        if ($type === 'upper') {
            $inputBound = $partition->getUpperBound();
        } else {
            $inputBound = $partition->getLowerBound();
        }

        $source = $this->partitionMetadata->getSource();

        $valueResolver = reset($source);

        if ($valueResolver === false) {
            throw new \RuntimeException('Partition source is empty');
        }

        return $valueResolver->transformSummaryValueToSourceValue($inputBound);
    }

    // public function clearDirtyPartitionEntityFromDatabase(Partition $partition): void
    // {
    //     $this->entityManager->createQueryBuilder()
    //         ->delete(SummarySignal::class, 'd')
    //         ->where('d.class = :class')
    //         ->andWhere('d.level = :level')
    //         ->andWhere('d.key = :key')
    //         ->setParameter('class', $this->metadata->getSummaryClass())
    //         ->setParameter('level', $partition->getLevel())
    //         ->setParameter('key', (string) $partition->getKey())
    //         ->getQuery()
    //         ->execute();
    // }
}
