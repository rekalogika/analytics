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

namespace Rekalogika\Analytics\Contracts\Context;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\PartitionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

/**
 * Context given to a `ValueResolver` or a `SummarizableAggregateFunction`
 * while the framework builds the DQL query that rolls up the source entities
 * into the summary table. It provides access to the query builder, for
 * resolving property paths of the source entity and for binding parameters,
 * and to the metadata of the summary and of the property being resolved.
 *
 * Only one of the partition, dimension, or measure metadata is available,
 * depending on which kind of property the context was created for.
 *
 * @see \Rekalogika\Analytics\Contracts\Summary\ValueResolver
 * @see \Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction
 *
 * @todo add method to get the parent context, and use that when calling
 * parent's method
 */
final readonly class SourceQueryContext
{
    public function __construct(
        private SimpleQueryBuilder $queryBuilder,
        private SummaryMetadata $summaryMetadata,
        private ?PartitionMetadata $partitionMetadata = null,
        private ?DimensionMetadata $dimensionMetadata = null,
        private ?MeasureMetadata $measureMetadata = null,
    ) {}

    /**
     * Resolves a property path of the source entity into a DQL expression.
     *
     * Path is a dot-separated string that represents a path to a property of an
     * entity, e.g. `customer.country`. This method resolves the path to a DQL
     * path, and joins the necessary tables. If the path resolves to a related
     * entity, you can prefix the path with * to force a join, and return the
     * alias.
     */
    public function resolve(string $path): string
    {
        return $this->queryBuilder->resolve($path);
    }

    /**
     * Binds a value as a named parameter of the query, and returns the
     * placeholder (e.g. `:p1`) to use in the DQL expression. Use this instead
     * of embedding literal values in the expression.
     *
     * Doctrine 2 does not have createNamedParameter method in QueryBuilder,
     * so we do it manually here.
     *
     * @param mixed $value The value to bind.
     * @param int|string|ParameterType|ArrayParameterType|null $type The DBAL
     * type of the parameter, if it cannot be inferred from the value.
     */
    public function createNamedParameter(
        mixed $value,
        int|string|ParameterType|ArrayParameterType|null $type = null,
    ): string {
        return $this->queryBuilder->createNamedParameter($value, $type);
    }

    /**
     * The metadata of the partition being resolved.
     *
     * @throws MetadataException If the context was not created for a
     * partition.
     */
    public function getPartitionMetadata(): PartitionMetadata
    {
        if (null === $this->partitionMetadata) {
            throw new MetadataException('Partition metadata is not set, probably because the context is not created for a partition.');
        }

        return $this->partitionMetadata;
    }

    /**
     * The metadata of the dimension being resolved.
     *
     * @throws MetadataException If the context was not created for a
     * dimension.
     */
    public function getDimensionMetadata(): DimensionMetadata
    {
        if (null === $this->dimensionMetadata) {
            throw new MetadataException('Dimension metadata is not set, probably because the context is not created for a dimension.');
        }

        return $this->dimensionMetadata;
    }

    /**
     * The metadata of the measure being resolved.
     *
     * @throws MetadataException If the context was not created for a measure.
     */
    public function getMeasureMetadata(): MeasureMetadata
    {
        if (null === $this->measureMetadata) {
            throw new MetadataException('Measure metadata is not set, probably because the context is not created for a measure.');
        }

        return $this->measureMetadata;
    }

    /**
     * The metadata of the summary class being refreshed.
     */
    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata;
    }
}
