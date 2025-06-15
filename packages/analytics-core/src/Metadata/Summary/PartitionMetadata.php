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

namespace Rekalogika\Analytics\Metadata\Summary;

use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\PartitionKeyClassifier;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Core\Util\LiteralString;

/**
 * @todo improve naming
 */
final readonly class PartitionMetadata extends PropertyMetadata
{
    /**
     * @param array<class-string,PartitionValueResolver<mixed>> $source
     * @param class-string<Partition> $partitionClass
     */
    public function __construct(
        private array $source,
        string $summaryProperty,
        private string $partitionClass,
        private string $partitionLevelProperty,
        private string $partitionKeyProperty,
        private PartitionKeyClassifier $partitionKeyClassifier,
        ?SummaryMetadata $summaryMetadata = null,
    ) {
        parent::__construct(
            summaryProperty: $summaryProperty,
            label: new LiteralString('Partition'),
            typeClass: $partitionClass,
            summaryMetadata: $summaryMetadata,
            hidden: true,
        );
    }

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            source: $this->source,
            summaryProperty: $this->getSummaryProperty(),
            partitionClass: $this->partitionClass,
            partitionLevelProperty: $this->partitionLevelProperty,
            partitionKeyProperty: $this->partitionKeyProperty,
            partitionKeyClassifier: $this->partitionKeyClassifier,
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * @return array<class-string,PartitionValueResolver<mixed>>
     */
    public function getSource(): array
    {
        return $this->source;
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

    public function getFullyQualifiedPartitionKeyProperty(): string
    {
        return $this->getSummaryProperty() . '.' . $this->partitionKeyProperty;
    }

    public function getFullyQualifiedPartitionLevelProperty(): string
    {
        return $this->getSummaryProperty() . '.' . $this->partitionLevelProperty;
    }
}
