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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultNormalRow;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @deprecated
 */
final class DimensionCollector
{
    /**
     * @var array<string,DimensionByNameCollector>
     */
    private array $collectors = [];

    /**
     * @var array<string,DefaultMeasure>
     */
    private array $measures = [];

    public function __construct(
        private SummaryMetadata $metadata,
        private DefaultQuery $query,
        private readonly DimensionFactory $dimensionFactory,
    ) {}

    public function getItemCollection(): ItemCollection
    {
        $uniqueDimensionsByKey = [];

        foreach ($this->collectors as $name => $collector) {
            $uniqueDimensionsByKey[$name] = $collector->getResult();
        }

        return new ItemCollection(
            dimensions: $uniqueDimensionsByKey,
            measures: $this->measures,
        );
    }

    private function getOrder(string $name): ?Order
    {
        $orderBy = $this->query->getOrderBy();

        foreach ($orderBy as $dimensionName => $order) {
            if ($dimensionName === $name) {
                return $order;
            }
        }

        try {
            $metadata = $this->metadata->getDimension($name);
        } catch (MetadataException) {
            $metadata = null;
        }

        if ($metadata !== null) {
            $order = $metadata->getOrderBy();

            if ($order instanceof Order) {
                return $order;
            }
        }

        return null;
    }

    private function getCollectorForName(string $name): DimensionByNameCollector
    {
        return $this->collectors[$name] ??= new DimensionByNameCollector(
            name: $name,
            order: $this->getOrder($name),
            dimensionFactory: $this->dimensionFactory,
        );
    }

    public function processDimensions(DefaultNormalRow $normalRow): void
    {
        $earlierDimensions = [];

        foreach ($normalRow->getTuple() as $dimension) {
            $name = $dimension->getName();

            $this->getCollectorForName($name)->addDimension($dimension);

            $earlierDimensions[] = $dimension;
        }
    }

    public function processMeasure(DefaultMeasure $measure): void
    {
        $this->measures[$measure->getName()]
            ??= DefaultMeasure::createNullFromSelf($measure);
    }
}
