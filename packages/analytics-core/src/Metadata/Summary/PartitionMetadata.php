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
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Contracts\Translation\LiteralString;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;

/**
 * Metadata of the partition property of a summary class. The partition is an
 * embeddable with a key property and a level property, and this metadata
 * knows the names of both, so the engine can address them in queries and
 * index them in the database.
 *
 * @see Partition
 *
 * @todo improve naming
 */
final readonly class PartitionMetadata extends PropertyMetadata
{
    /**
     * @param PartitionValueResolver<mixed> $source Resolves the partitioning
     * value of the source entity.
     * @param string $summaryProperty The name of the partition property in
     * the summary class.
     * @param class-string<Partition> $partitionClass The embeddable class.
     * @param string $partitionLevelProperty The property of the embeddable
     * having the `PartitionLevel` attribute.
     * @param string $partitionKeyProperty The property of the embeddable
     * having the `PartitionKey` attribute.
     * @param AttributeCollection $attributes The attributes of the property.
     * @param SummaryMetadata|null $summaryMetadata The owning summary.
     */
    public function __construct(
        private PartitionValueResolver $source,
        string $summaryProperty,
        private string $partitionClass,
        private string $partitionLevelProperty,
        private string $partitionKeyProperty,
        AttributeCollection $attributes,
        ?SummaryMetadata $summaryMetadata = null,
    ) {
        parent::__construct(
            name: $summaryProperty,
            propertyName: $summaryProperty,
            label: new LiteralString('Partition'),
            typeClass: $partitionClass,
            summaryMetadata: $summaryMetadata,
            attributes: $attributes,
            involvedSourceProperties: $source->getInvolvedProperties(),
            hidden: true,
        );
    }

    /**
     * Returns a copy of this partition attached to the given summary. Called
     * by `SummaryMetadata`.
     */
    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            source: $this->source,
            summaryProperty: $this->getName(),
            partitionClass: $this->partitionClass,
            partitionLevelProperty: $this->partitionLevelProperty,
            partitionKeyProperty: $this->partitionKeyProperty,
            attributes: $this->getAttributes(),
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * Resolves the partitioning value of the source entity, typically its
     * primary key.
     *
     * @return PartitionValueResolver<mixed>
     */
    public function getSource(): PartitionValueResolver
    {
        return $this->source;
    }

    /**
     * The embeddable class implementing the `Partition` contract.
     *
     * @return class-string<Partition>
     */
    public function getPartitionClass(): string
    {
        return $this->partitionClass;
    }

    /**
     * The name of the level property inside the embeddable, e.g. `level`.
     */
    public function getPartitionLevelProperty(): string
    {
        return $this->partitionLevelProperty;
    }

    /**
     * The name of the key property inside the embeddable, e.g. `key`.
     */
    public function getPartitionKeyProperty(): string
    {
        return $this->partitionKeyProperty;
    }

    /**
     * The path of the key property from the summary class, e.g.
     * `partition.key`, as used in DQL.
     */
    public function getFullyQualifiedPartitionKeyProperty(): string
    {
        return $this->getName() . '.' . $this->partitionKeyProperty;
    }

    /**
     * The path of the level property from the summary class, e.g.
     * `partition.level`, as used in DQL.
     */
    public function getFullyQualifiedPartitionLevelProperty(): string
    {
        return $this->getName() . '.' . $this->partitionLevelProperty;
    }
}
