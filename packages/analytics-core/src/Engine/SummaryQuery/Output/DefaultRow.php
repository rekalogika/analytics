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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Contracts\Rekapager\PageableInterface;

/**
 * @implements PageableInterface<int,object>
 */
final class DefaultRow implements Row, PageableInterface
{
    use MeasuresTrait;
    use PageableTrait;

    /**
     * @param list<string> $dimensionality
     */
    public function __construct(
        private readonly DefaultCell $cell,
        private readonly array $dimensionality,
        private readonly SourceEntitiesFactory $sourceEntitiesFactory,
    ) {}

    #[\Override]
    private function getSourceEntitiesFactory(): SourceEntitiesFactory
    {
        return $this->sourceEntitiesFactory;
    }

    #[\Override]
    public function getCoordinates(): DefaultOrderedCoordinates
    {
        return $this->cell->getCoordinates()->withOrder($this->dimensionality);
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->cell->getMeasures();
    }
}
