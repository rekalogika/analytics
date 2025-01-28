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

namespace Rekalogika\Analytics\Metadata;

use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\PartitionKeyClassifier;
use Rekalogika\Analytics\PartitionValueResolver;
use Rekalogika\Analytics\Util\PartitionUtil;

final readonly class PartitionMetadata
{
    /**
     * @param array<class-string,PartitionValueResolver> $source
     * @param class-string<Partition> $partitionClass
     */
    public function __construct(
        private array $source,
        private string $summaryProperty,
        private string $partitionClass,
        private string $partitionLevelProperty,
        private string $partitionKeyProperty,
        private PartitionKeyClassifier $partitionKeyClassifier,
    ) {}

    /**
     * @return array<class-string,PartitionValueResolver>
     */
    public function getSource(): array
    {
        return $this->source;
    }

    public function getSummaryProperty(): string
    {
        return $this->summaryProperty;
    }

    /**
     * @return class-string<Partition>
     */
    public function getPartitionClass(): string
    {
        return $this->partitionClass;
    }

    public function getPartitionLevelProperty(): string
    {
        return $this->partitionLevelProperty;
    }

    public function getPartitionKeyProperty(): string
    {
        return $this->partitionKeyProperty;
    }

    public function createPartitionFromSourceValue(
        mixed $sourceValue,
        int $level,
    ): Partition {
        $partitionClass = $this->partitionClass;

        $source = $this->source;
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
        $partitionClass = $this->partitionClass;
        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);

        return $this->createPartitionFromSourceValue($sourceValue, $lowestLevel);
    }

    public function createHighestPartitionFromSourceValue(
        mixed $sourceValue,
    ): Partition {
        $partitionClass = $this->partitionClass;
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

        $source = $this->source;
        $valueResolver = reset($source);

        if ($valueResolver === false) {
            throw new \RuntimeException('Partition source is empty');
        }

        return $valueResolver->transformSummaryValueToSourceValue($inputBound);
    }

    public function getKeyClassifier(): PartitionKeyClassifier
    {
        return $this->partitionKeyClassifier;
    }
}
