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

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\CellRepository;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\DimensionCollection;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\MetadataOrderByResolver;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\NullMeasureCollection;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @internal
 */
final readonly class ResultContext
{
    private DimensionCollection $dimensionCollection;

    private DimensionFactory $dimensionFactory;

    private NullMeasureCollection $nullMeasureCollection;

    private CellRepository $cellRepository;

    private TranslatableInterface $label;

    public function __construct(
        SummaryMetadata $metadata,
        private DefaultQuery $query,
        SourceEntitiesFactory $sourceEntitiesFactory,
        int $nodesLimit,
        private ResultContextFactory $resultContextFactory,
    ) {
        $this->label = $metadata->getLabel();

        $orderByResolver = new MetadataOrderByResolver(
            metadata: $metadata,
            query: $query,
        );

        $this->dimensionFactory = new DimensionFactory(
            nodesLimit: $nodesLimit,
        );

        $this->dimensionCollection = new DimensionCollection(
            dimensionFactory: $this->dimensionFactory,
            orderByResolver: $orderByResolver,
        );

        $this->nullMeasureCollection = new NullMeasureCollection();

        $this->cellRepository = new CellRepository(
            dimensionCollection: $this->dimensionCollection,
            nullMeasureCollection: $this->nullMeasureCollection,
            query: $query,
            context: $this,
            sourceEntitiesFactory: $sourceEntitiesFactory,
        );
    }

    public function getSummaryLabel(): TranslatableInterface
    {
        return $this->label;
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

    public function getApexCell(): DefaultCell
    {
        return $this->cellRepository->getApexCell();
    }

    public function withDicePredicate(?Expression $predicate): self
    {
        $newQuery = clone $this->query;
        $newQuery->dice($predicate);

        return $this->resultContextFactory->createResultContext($newQuery);
    }
}
