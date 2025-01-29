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

    public function getKeyClassifier(): PartitionKeyClassifier
    {
        return $this->partitionKeyClassifier;
    }
}
