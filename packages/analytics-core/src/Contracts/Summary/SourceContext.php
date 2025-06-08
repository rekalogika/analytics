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

namespace Rekalogika\Analytics\Contracts\Summary;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionLevelMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionLevelPropertyMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionPathMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\PartitionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final readonly class SourceContext
{
    public function __construct(
        private SimpleQueryBuilder $queryBuilder,
        private SummaryMetadata $summaryMetadata,
        private ?PartitionMetadata $partitionMetadata = null,
        private ?DimensionMetadata $dimensionMetadata = null,
        private ?MeasureMetadata $measureMetadata = null,
        private ?DimensionLevelPropertyMetadata $dimensionPropertyMetadata = null,
    ) {}

    /**
     * Path is a dot-separated string that represents a path to a property of an
     * entity. This method resolves the path to a DQL path, and joins the
     * necessary tables. If the path resolves to a related entity, you can
     * prefix the path with * to force a join, and return the alias.
     */
    public function resolve(string $path): string
    {
        return $this->queryBuilder->resolve($path);
    }

    /**
     * Doctrine 2 does not have createNamedParameter method in QueryBuilder,
     * so we do it manually here.
     */
    public function createNamedParameter(
        mixed $value,
        int|string|ParameterType|ArrayParameterType|null $type = null,
    ): string {
        return $this->queryBuilder->createNamedParameter($value, $type);
    }

    public function getPartitionMetadata(): PartitionMetadata
    {
        if (null === $this->partitionMetadata) {
            throw new MetadataException('Partition metadata is not set, probably because the context is not created for a partition.');
        }

        return $this->partitionMetadata;
    }

    public function getDimensionMetadata(): DimensionMetadata
    {
        if (null === $this->dimensionMetadata) {
            throw new MetadataException('Dimension metadata is not set, probably because the context is not created for a dimension.');
        }

        return $this->dimensionMetadata;
    }

    public function getMeasureMetadata(): MeasureMetadata
    {
        if (null === $this->measureMetadata) {
            throw new MetadataException('Measure metadata is not set, probably because the context is not created for a measure.');
        }

        return $this->measureMetadata;
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata;
    }

    public function getDimensionPropertyMetadata(): DimensionLevelPropertyMetadata
    {
        if (null === $this->dimensionPropertyMetadata) {
            throw new MetadataException('Dimension property metadata is not set, probably because the context is not created for a property inside a hierarchical dimension.');
        }

        return $this->dimensionPropertyMetadata;
    }

    public function getDimensionLevelMetadata(): DimensionLevelMetadata
    {
        return $this->getDimensionPropertyMetadata()->getLevelMetadata();
    }

    public function getDimensionPathMetadata(): DimensionPathMetadata
    {
        return $this->getDimensionLevelMetadata()->getPathMetadata();
    }

    public function getDimensionHierarchyMetadata(): DimensionHierarchyMetadata
    {
        return $this->getDimensionPathMetadata()->getHierarchyMetadata();
    }
}
