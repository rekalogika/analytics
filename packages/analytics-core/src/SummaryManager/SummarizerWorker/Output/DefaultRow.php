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

use Rekalogika\Analytics\Query\Measure;
use Rekalogika\Analytics\Query\Measures;
use Rekalogika\Analytics\Query\Row;
use Rekalogika\Analytics\Query\Tuple;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;

final readonly class DefaultRow implements Row
{
    public function __construct(
        private Tuple $tuple,
        private Measures $measures,
        private string $groupings,
    ) {}

    public static function createFromResultRow(ResultRow $resultRow): self
    {
        $tuple = DefaultTuple::fromResultTuple($resultRow->getTuple());

        $measures = array_map(
            static fn(ResultValue $resultValue): Measure => DefaultMeasure::createFromResultValue($resultValue),
            $resultRow->getMeasures(),
        );

        $measures = DefaultMeasures::fromMeasures($measures);

        return new self(
            tuple: $tuple,
            measures: $measures,
            groupings: $resultRow->getGroupings(),
        );
    }

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
