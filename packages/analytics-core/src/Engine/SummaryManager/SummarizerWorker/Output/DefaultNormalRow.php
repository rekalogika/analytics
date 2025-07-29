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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\NormalRow;
use Rekalogika\Analytics\Engine\Util\DimensionUtil;

final readonly class DefaultNormalRow implements NormalRow
{
    public function __construct(
        private DefaultTuple $tuple,
        private DefaultMeasure $measure,
        private ?GroupingField $groupings,
    ) {}

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

    public function getGroupings(): ?GroupingField
    {
        return $this->groupings;
    }

    /**
     * @param array<string,int<0,max>> $measures
     * @return -1|0|1
     */
    public static function compare(self $row1, self $row2, array $measures): int
    {
        foreach ($row1->getTuple() as $name => $value1) {
            $value2 = $row2->getTuple()->getByKey($name);

            if ($value2 === null) {
                return 1;
            }

            if ($name === '@values') {
                $measure1Order = $measures[$row1->getMeasure()->getName()]
                    ?? throw new LogicException('Measure not found');

                $measure2Order = $measures[$row2->getMeasure()->getName()]
                    ?? throw new LogicException('Measure not found');

                $comparison = $measure1Order <=> $measure2Order;

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            if (!DimensionUtil::isDimensionSame($value1, $value2)) {
                return 0;
            }
        }

        return 0;
    }

    public function getWithoutValues(): DefaultTuple
    {
        return $this->tuple->getWithoutValues();
    }

    public function getSignature(): string
    {
        return $this->tuple->getSignature();
    }

    public function isSubtotal(): bool
    {
        return $this->groupings?->isSubtotal() ?? false;
    }
}
