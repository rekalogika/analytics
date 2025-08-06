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

use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\OrderedTuple;
use Rekalogika\Analytics\Contracts\Result\Row;

final class DefaultRow implements Row
{
    /**
     * @param list<string> $dimensionality
     */
    public function __construct(
        private readonly DefaultCell $cell,
        private readonly array $dimensionality,
    ) {}

    #[\Override]
    public function getTuple(): OrderedTuple
    {
        return new DefaultOrderedTuple(
            tuple: $this->cell->getTuple(),
            order: $this->dimensionality,
        );
    }

    #[\Override]
    public function getMeasures(): Measures
    {
        return $this->cell->getMeasures();
    }
}
