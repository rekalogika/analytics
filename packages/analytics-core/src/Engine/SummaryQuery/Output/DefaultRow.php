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

final class DefaultRow implements Row
{
    use MeasuresTrait;

    /**
     * @param list<string> $dimensionality
     */
    public function __construct(
        private readonly DefaultCell $cell,
        private readonly array $dimensionality,
    ) {}

    #[\Override]
    public function getTuple(): DefaultOrderedTuple
    {
        return $this->cell->getTuple()->withOrder($this->dimensionality);
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->cell->getMeasures();
    }
}
