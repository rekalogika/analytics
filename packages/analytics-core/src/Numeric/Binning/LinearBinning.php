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
 * Bins of equal width.
 *
 * Without `min` and `max`, the bins extend indefinitely in both directions,
 * and are aligned to zero: with a width of 10, the bin with index 0 is 0 to
 * below 10, index 1 is 10 to below 20, and index -1 is -10 to below 0.
 *
 * With `min`, the bins are aligned to `min` instead, and the values below
 * `min` go to a single bin with index -1. With `max`, the values from `max`
 * go to a single bin. Example: a width of 10, a `min` of 26 and a `max` of 66
 * give: below 26, 26 to below 36, 36 to below 46, 46 to below 56, 56 to below
 * 66, and 66 and above. If `max - min` is not a multiple of the width, the
 * last bin before `max` is narrower.
 */
final readonly class LinearBinning implements Binning
{
    /**
     * The index of the bin that starts at `max`, if `max` is given.
     */
    private ?int $overflowIndex;

    /**
     * @param int|float $width The width of each bin, must be positive.
     * @param int|float|null $min The lower bound of the first regular bin.
     * Values below it go to a single bin. Null for no lower bound.
     * @param int|float|null $max The lower bound of the last bin, which gets
     * all the values from it. Null for no upper bound.
     */
    public function __construct(
        private int|float $width,
        private int|float|null $min = null,
        private int|float|null $max = null,
    ) {
        NumberUtil::assertFinite($width, 'width');

        if ($width <= 0) {
            throw new InvalidArgumentException('The width must be positive.');
        }

        if ($min !== null) {
            NumberUtil::assertFinite($min, 'min');
        }

        if ($max !== null) {
            NumberUtil::assertFinite($max, 'max');

            if ($min !== null && $max <= $min) {
                throw new InvalidArgumentException('"max" must be greater than "min".');
            }

            $this->overflowIndex = NumberUtil::countSteps(
                ((float) $max - (float) $this->getOrigin()) / (float) $width,
            );
        } else {
            $this->overflowIndex = null;
        }
    }

    public function getWidth(): int|float
    {
        return $this->width;
    }

    public function getMin(): int|float|null
    {
        return $this->min;
    }

    public function getMax(): int|float|null
    {
        return $this->max;
    }

    private function getOrigin(): int|float
    {
        return $this->min ?? 0;
    }

    #[\Override]
    public function getDQLExpression(string $input): string
    {
        $origin = $this->getOrigin();

        if ($origin > 0) {
            $dividend = \sprintf('%s - %s', $input, NumberUtil::toDql($origin));
        } elseif ($origin < 0) {
            $dividend = \sprintf('%s + %s', $input, NumberUtil::toDql(-$origin));
        } else {
            $dividend = $input;
        }

        $floor = \sprintf(
            'REKALOGIKA_FLOOR_DIV(%s, %s)',
            $dividend,
            NumberUtil::toDql($this->width),
        );

        if ($this->min === null && $this->max === null) {
            return $floor;
        }

        $whens = [];

        if ($this->min !== null) {
            $whens[] = \sprintf(
                'WHEN %s < %s THEN -1',
                $input,
                NumberUtil::toDql($this->min),
            );
        }

        if ($this->max !== null) {
            $whens[] = \sprintf(
                'WHEN %s >= %s THEN %d',
                $input,
                NumberUtil::toDql($this->max),
                $this->overflowIndex ?? 0,
            );
        }

        return \sprintf('CASE %s ELSE %s END', implode(' ', $whens), $floor);
    }

    #[\Override]
    public function getFirstIndex(): ?int
    {
        return $this->min === null ? null : -1;
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

        if ($this->min !== null && $index === -1) {
            return null;
        }

        if ($index === $this->overflowIndex) {
            return $this->max;
        }

        return NumberUtil::linear($this->getOrigin(), $index, $this->width);
    }

    #[\Override]
    public function getUpperBound(int $index): int|float|null
    {
        $this->assertIndex($index);

        if ($this->min !== null && $index === -1) {
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

        return NumberUtil::linear($this->getOrigin(), $index + 1, $this->width);
    }

    private function assertIndex(int $index): void
    {
        $first = $this->getFirstIndex();
        $last = $this->getLastIndex();

        if (
            ($first !== null && $index < $first)
            || ($last !== null && $index > $last)
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Bin index %d is out of range, expected %s to %s.',
                $index,
                $first ?? '-∞',
                $last ?? '∞',
            ));
        }
    }
}
