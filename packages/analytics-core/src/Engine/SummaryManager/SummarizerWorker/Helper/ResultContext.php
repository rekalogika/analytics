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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Engine\SummaryManager\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\MetadataOrderByResolver;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\NullMeasureCollection;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @internal
 */
final class ResultContext
{
    private readonly DimensionCollection $dimensionCollection;

    private readonly DimensionFactory $dimensionFactory;

    private readonly NullMeasureCollection $nullMeasureCollection;

    public function __construct(
        SummaryMetadata $metadata,
        DefaultQuery $query,
    ) {
        $this->dimensionFactory = new DimensionFactory(
            new MetadataOrderByResolver(
                metadata: $metadata,
                query: $query,
            ),
        );

        $this->dimensionCollection = $this->dimensionFactory->getDimensionCollection();
        $this->nullMeasureCollection = new NullMeasureCollection();
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
}
