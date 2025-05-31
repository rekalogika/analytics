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

use Rekalogika\Analytics\Contracts\Result\Row;

final readonly class DefaultRow implements Row
{
    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private string $summaryClass,
        private DefaultTuple $tuple,
        private DefaultMeasures $measures,
        private string $groupings,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getTuple(): DefaultTuple
    {
        return $this->tuple;
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
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
