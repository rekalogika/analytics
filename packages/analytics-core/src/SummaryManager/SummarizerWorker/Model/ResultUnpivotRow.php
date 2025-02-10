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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

/**
 * @internal
 */
final readonly class ResultUnpivotRow
{
    private ResultTuple $tuple;

    /**
     * @param non-empty-array<string,ResultValue> $dimensions
     */
    public function __construct(
        private readonly object $object,
        private array $dimensions,
        private ResultValue $measure,
    ) {
        $this->tuple = new ResultTuple($dimensions);
    }

    /**
     * @param array<string,int<0,max>> $measures
     * @return -1|0|1
     */
    public static function compare(self $row1, self $row2, array $measures): int
    {
        $dimensions1 = $row1->getDimensions();
        $dimensions2 = $row2->getDimensions();

        foreach ($dimensions1 as $key => $value1) {
            $value2 = $dimensions2[$key];

            if ($key === '@values') {
                break;
            }

            if (!$value1->isSame($value2)) {
                return 0;
            }
        }

        $measure1Order = $measures[$row1->getMeasure()->getField()]
            ?? throw new \RuntimeException('Measure not found');

        $measure2Order = $measures[$row2->getMeasure()->getField()]
            ?? throw new \RuntimeException('Measure not found');

        return $measure1Order <=> $measure2Order;
    }

    public function hasSameTuple(self $other): bool
    {
        return $this->tuple->isSame($other->tuple);
    }

    public function getTuple(): ResultTuple
    {
        return $this->tuple;
    }

    public function getObject(): object
    {
        return $this->object;
    }

    /**
     * @return non-empty-array<string,ResultValue>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    public function getLastDimension(): ResultValue
    {
        $dimensions = $this->dimensions;

        return end($dimensions);
    }

    public function getMeasure(): ResultValue
    {
        return $this->measure;
    }
}
