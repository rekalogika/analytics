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

namespace Rekalogika\Analytics\Numeric\Binning;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Numeric\Binning;
use Rekalogika\Analytics\Numeric\Util\NumberUtil;

/**
 * Bins whose bounds grow geometrically, suitable for values spanning several
 * orders of magnitude, like prices or quantities.
 *
 * The bin with index `i` is `min * base^i` to below `min * base^(i+1)`. The
 * values below `min`, including zero and negative numbers, go to a single bin
 * with index -1. With the defaults: below 1, 1 to below 10, 10 to below 100,
 * 100 to below 1000, and so on.
 *
 * With `max`, the values from `max` go to a single bin. If `max` is not `min`
 * times a power of `base`, the last bin before `max` is narrower.
 */
final readonly class LogarithmicBinning implements Binning
{
    /**
     * The index of the bin that starts at `max`, if `max` is given.
     */
    private ?int $overflowIndex;

    /**
     * @param int|float $base The ratio between the bounds of a bin, must be
     * greater than 1.
     * @param int|float $min The lower bound of the first regular bin, must be
     * positive. Values below it go to a single bin.
     * @param int|float|null $max The lower bound of the last bin, which gets
     * all the values from it. Null for no upper bound.
     */
    public function __construct(
        private int|float $base = 10,
        private int|float $min = 1,
        private int|float|null $max = null,
    ) {
        NumberUtil::assertFinite($base, 'base');
        NumberUtil::assertFinite($min, 'min');

        if ($base <= 1) {
            throw new InvalidArgumentException('The base must be greater than 1.');
        }

        if ($min <= 0) {
            throw new InvalidArgumentException('"min" must be positive.');
        }

        if ($max !== null) {
            NumberUtil::assertFinite($max, 'max');

            if ($max <= $min) {
                throw new InvalidArgumentException('"max" must be greater than "min".');
            }

            // e.g. log(1000) / log(10) is 2.9999999999999996, but gives 3
            $this->overflowIndex = NumberUtil::countSteps(
                log((float) $max / (float) $min) / log((float) $base),
            );
        } else {
            $this->overflowIndex = null;
        }
    }

    public function getBase(): int|float
    {
        return $this->base;
    }

    public function getMin(): int|float
    {
        return $this->min;
    }

    public function getMax(): int|float|null
    {
        return $this->max;
    }

    #[\Override]
    public function getDQLExpression(string $input): string
    {
        $whens = [];

        $whens[] = \sprintf(
            'WHEN %s < %s THEN -1',
            $input,
            NumberUtil::toDql($this->min),
        );

        if ($this->max !== null) {
            $whens[] = \sprintf(
                'WHEN %s >= %s THEN %d',
                $input,
                NumberUtil::toDql($this->max),
                $this->overflowIndex ?? 0,
            );
        }

        // the logarithm is only taken of the values from min, so it never
        // sees zero or a negative number
        return \sprintf(
            'CASE %s ELSE REKALOGIKA_FLOOR_LOG(%s, %s, %s) END',
            implode(' ', $whens),
            $input,
            NumberUtil::toDql($this->base),
            NumberUtil::toDql($this->min),
        );
    }

    #[\Override]
    public function getFirstIndex(): int
    {
        return -1;
    }

    #[\Override]
    public function getLastIndex(): ?int
    {
        return $this->overflowIndex;
    }

    #[\Override]
    public function getLowerBound(int $index): int|float|null
    {
        $this->assertIndex($index);

        if ($index === -1) {
            return null;
        }

        if ($index === $this->overflowIndex) {
            return $this->max;
        }

        return NumberUtil::geometric($this->min, $this->base, $index);
    }

    #[\Override]
    public function getUpperBound(int $index): int|float|null
    {
        $this->assertIndex($index);

        if ($index === -1) {
            return $this->min;
        }

        if ($this->overflowIndex !== null) {
            if ($index === $this->overflowIndex) {
                return null;
            }

            if ($index === $this->overflowIndex - 1) {
                return $this->max;
            }
        }

        return NumberUtil::geometric($this->min, $this->base, $index + 1);
    }

    private function assertIndex(int $index): void
    {
        if (
            $index < -1
            || ($this->overflowIndex !== null && $index > $this->overflowIndex)
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Bin index %d is out of range, expected -1 to %s.',
                $index,
                $this->overflowIndex ?? '∞',
            ));
        }
    }
}
