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

final class DefaultRow implements Row
{
    use MeasuresTrait;

    public function __construct(
        private readonly DefaultCell $cell,
        private readonly SourceEntitiesFactory $sourceEntitiesFactory,
    ) {}

    /**
     * @return PageableInterface<int,object>
     */
    #[\Override]
    public function getSourceEntities(): PageableInterface
    {
        return $this->sourceEntitiesFactory
            ->getSourceEntities($this->getCoordinates());
    }

    #[\Override]
    public function getCoordinates(): DefaultCoordinates
    {
        return $this->cell->getCoordinates();
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->cell->getMeasures();
    }
}
