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
 * Bins with arbitrary boundaries. The thresholds `[31, 59, 109]` give four
 * bins: below 31, 31 to below 59, 59 to below 109, and 109 and above, with the
 * indexes 0 to 3.
 *
 * To give the bins your own labels, use a `RangeBin` enum instead.
 */
final readonly class ThresholdBinning implements Binning
{
    /**
     * @var non-empty-list<int|float>
     */
    private array $thresholds;

    /**
     * @param list<int|float> $thresholds The boundaries between the bins, in
     * strictly ascending order. Each is the lower bound (inclusive) of a bin.
     */
    public function __construct(array $thresholds)
    {
        NumberUtil::assertThresholds($thresholds);

        $this->thresholds = $thresholds;
    }

    /**
     * @return non-empty-list<int|float>
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    #[\Override]
    public function getDQLExpression(string $input): string
    {
        return NumberUtil::getThresholdCaseExpression(
            input: $input,
            thresholds: $this->thresholds,
            outputs: array_map(
                strval(...),
                range(0, \count($this->thresholds)),
            ),
        );
    }

    #[\Override]
    public function getFirstIndex(): int
    {
        return 0;
    }

    #[\Override]
    public function getLastIndex(): int
    {
        return \count($this->thresholds);
    }

    #[\Override]
    public function getLowerBound(int $index): int|float|null
    {
        $this->assertIndex($index);

        return [null, ...$this->thresholds][$index] ?? null;
    }

    #[\Override]
    public function getUpperBound(int $index): int|float|null
    {
        $this->assertIndex($index);

        return $this->thresholds[$index] ?? null;
    }

    private function assertIndex(int $index): void
    {
        if ($index < 0 || $index > \count($this->thresholds)) {
            throw new InvalidArgumentException(\sprintf(
                'Bin index %d is out of range, expected 0 to %d.',
                $index,
                \count($this->thresholds),
            ));
        }
    }
}
