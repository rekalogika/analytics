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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Helper;

use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\CellRepository;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\DimensionCollection;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\MetadataOrderByResolver;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\NullMeasureCollection;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @internal
 */
final readonly class ResultContext
{
    private DimensionCollection $dimensionCollection;

    private DimensionFactory $dimensionFactory;

    private NullMeasureCollection $nullMeasureCollection;

    private CellRepository $cellRepository;

    public function __construct(
        private SummaryMetadata $metadata,
        private DefaultQuery $query,
        private SourceEntitiesFactory $sourceEntitiesFactory,
        int $nodesLimit,
    ) {
        $orderByResolver = new MetadataOrderByResolver(
            metadata: $metadata,
            query: $query,
        );

        $this->dimensionFactory = new DimensionFactory(
            orderByResolver: $orderByResolver,
            nodesLimit: $nodesLimit,
        );

        $this->dimensionCollection = $this->dimensionFactory->getDimensionCollection();
        $this->nullMeasureCollection = new NullMeasureCollection();

        $this->cellRepository = new CellRepository(
            dimensionCollection: $this->dimensionCollection,
            nullMeasureCollection: $this->nullMeasureCollection,
            query: $this->query,
            context: $this,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
        );
    }

    public function getQuery(): DefaultQuery
    {
        return $this->query;
    }

    public function getMetadata(): SummaryMetadata
    {
        return $this->metadata;
    }

    public function getDimensionCollection(): DimensionCollection
    {
        return $this->dimensionCollection;
    }

    public function getDimensionFactory(): DimensionFactory
    {
        return $this->dimensionFactory;
    }

    public function getNullMeasureCollection(): NullMeasureCollection
    {
        return $this->nullMeasureCollection;
    }

    public function getCellRepository(): CellRepository
    {
        return $this->cellRepository;
    }
}
