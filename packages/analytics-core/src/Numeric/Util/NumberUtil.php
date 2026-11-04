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

namespace Rekalogika\Analytics\Numeric\Util;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

/**
 * @internal
 */
final readonly class NumberUtil
{
    private function __construct() {}

    public static function assertFinite(int|float $number, string $name): void
    {
        if (\is_float($number) && !is_finite($number)) {
            throw new InvalidArgumentException(\sprintf(
                '"%s" must be a finite number, got %s.',
                $name,
                var_export($number, true),
            ));
        }
    }

    /**
     * @param list<int|float> $thresholds
     * @phpstan-assert non-empty-list<int|float> $thresholds
     * @psalm-assert non-empty-list<int|float> $thresholds
     */
    public static function assertThresholds(array $thresholds): void
    {
        if ($thresholds === []) {
            throw new InvalidArgumentException('The thresholds must not be empty.');
        }

        $previous = null;

        foreach ($thresholds as $threshold) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!\is_int($threshold) && !\is_float($threshold)) {
                throw new InvalidArgumentException(\sprintf(
                    'The thresholds must be numbers, got %s.',
                    get_debug_type($threshold),
                ));
            }

            self::assertFinite($threshold, 'threshold');

            if ($previous !== null && $threshold <= $previous) {
                throw new InvalidArgumentException(\sprintf(
                    'The thresholds must be in strictly ascending order, but %s follows %s.',
                    self::format($threshold),
                    self::format($previous),
                ));
            }

            $previous = $threshold;
        }
    }

    /**
     * Returns `$start + $index * $step`, as an integer if the operands are
     * integers.
     */
    public static function linear(
        int|float $start,
        int $index,
        int|float $step,
    ): int|float {
        if (\is_int($start) && \is_int($step)) {
            return $start + $index * $step;
        }

        return (float) $start + (float) $index * (float) $step;
    }

    /**
     * Returns `$start * $base ^ $index`, as an integer if the operands are
     * integers, and the index is not negative.
     */
    public static function geometric(
        int|float $start,
        int|float $base,
        int $index,
    ): int|float {
        $result = (float) $start * ((float) $base) ** ((float) $index);

        // exact as long as it is within the integer precision of a float
        if (
            \is_int($start)
            && \is_int($base)
            && $index >= 0
            && abs($result) < 2 ** 53
        ) {
            return (int) round($result);
        }

        return $result;
    }

    /**
     * The number of bins of the given step needed to reach the end from the
     * start, tolerating floating point noise, e.g. (0.3 - 0) / 0.1 is
     * 2.9999999999999996, but gives 3.
     */
    public static function countSteps(float $steps): int
    {
        $rounded = round($steps);

        if (abs($steps - $rounded) < 1e-9) {
            return (int) $rounded;
        }

        return (int) ceil($steps);
    }

    /**
     * Converts a number to a DQL literal. The value is embedded as a literal
     * and not as a parameter, because the expression is also used in the
     * GROUP BY clause, and the database must be able to match the two.
     */
    public static function toDql(int|float $number): string
    {
        self::assertFinite($number, 'number');

        if (\is_int($number)) {
            return (string) $number;
        }

        // var_export() gives the shortest representation that converts back
        // to the same float, always with a decimal point or an exponent
        return var_export($number, true);
    }

    /**
     * Formats a number for display, without the floating point noise, e.g.
     * 0.30000000000000004 becomes 0.3.
     */
    public static function format(int|float $number): string
    {
        if (\is_int($number)) {
            return (string) $number;
        }

        if ($number === floor($number) && abs($number) < 1e15) {
            return \sprintf('%.0F', $number);
        }

        return (string) round($number, 10);
    }

    /**
     * Returns a DQL CASE expression that maps the input to one of the
     * outputs, by comparing it against the thresholds. Returns null if the
     * input is null.
     *
     * @param non-empty-list<int|float> $thresholds Ascending.
     * @param non-empty-list<string> $outputs DQL expressions, one more than the
     * thresholds.
     */
    public static function getThresholdCaseExpression(
        string $input,
        array $thresholds,
        array $outputs,
    ): string {
        if (\count($outputs) !== \count($thresholds) + 1) {
            throw new InvalidArgumentException(\sprintf(
                'Expected %d outputs for %d thresholds, got %d.',
                \count($thresholds) + 1,
                \count($thresholds),
                \count($outputs),
            ));
        }

        $last = array_pop($outputs);
        $lastThreshold = $thresholds[array_key_last($thresholds)];

        $whens = [];

        foreach ($thresholds as $i => $threshold) {
            $whens[] = \sprintf(
                'WHEN %s < %s THEN %s',
                $input,
                self::toDql($threshold),
                $outputs[$i],
            );
        }

        // the last bin is tested explicitly, so a null input falls through
        // to ELSE, instead of landing in the last bin
        $whens[] = \sprintf(
            'WHEN %s >= %s THEN %s',
            $input,
            self::toDql($lastThreshold),
            $last,
        );

        return \sprintf(
            'CASE %s ELSE REKALOGIKA_NULL() END',
            implode(' ', $whens),
        );
    }
}
