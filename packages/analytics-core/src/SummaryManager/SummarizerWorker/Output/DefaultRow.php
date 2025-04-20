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
    public function __construct(
        private DefaultDimensions $dimensions,
        private DefaultMeasures $measures,
        private string $groupings,
    ) {}

    #[\Override]
    public function getDimensions(): DefaultDimensions
    {
        return $this->dimensions;
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
