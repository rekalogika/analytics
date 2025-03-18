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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Measures;
use Rekalogika\Analytics\Contracts\Row;
use Rekalogika\Analytics\Contracts\Tuple;

final readonly class DefaultRow implements Row
{
    public function __construct(
        private Tuple $tuple,
        private Measures $measures,
        private string $groupings,
    ) {}

    #[\Override]
    public function getTuple(): Tuple
    {
        return $this->tuple;
    }

    #[\Override]
    public function getMeasures(): Measures
    {
        return $this->measures;
    }

    public function getGroupings(): string
    {
        return $this->groupings;
    }

    public function isSubtotal(): bool
    {
        return substr_count($this->groupings, '1') !== 0;
    }
}
