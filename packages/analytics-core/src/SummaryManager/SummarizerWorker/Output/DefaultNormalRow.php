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

use Rekalogika\Analytics\Contracts\Result\NormalRow;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Util\DimensionUtil;

final readonly class DefaultNormalRow implements NormalRow
{
    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private string $summaryClass,
        private DefaultTuple $tuple,
        private DefaultMeasure $measure,
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
    public function getMeasure(): DefaultMeasure
    {
        return $this->measure;
    }

    public function getGroupings(): string
    {
        return $this->groupings;
    }

    /**
     * @param array<string,int<0,max>> $measures
     * @return -1|0|1
     */
    public static function compare(self $row1, self $row2, array $measures): int
    {
        $tuple1 = $row1->getTuple();
        $tuple2 = $row2->getTuple();

        foreach ($tuple1 as $name => $value1) {
            $value2 = $tuple2->getByName($name);

            if ($name === '@values') {
                break;
            }

            if (!DimensionUtil::isDimensionSame($value1, $value2)) {
                return 0;
            }
        }

        $measure1Order = $measures[$row1->getMeasure()->getName()]
            ?? throw new LogicException('Measure not found');

        $measure2Order = $measures[$row2->getMeasure()->getName()]
            ?? throw new LogicException('Measure not found');

        return $measure1Order <=> $measure2Order;
    }

    public function hasSameDimensions(self $other): bool
    {
        return $this->tuple->isSame($other->tuple);
    }
}
