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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;

/**
 * @template T of object
 */
final readonly class Numbers implements \Stringable
{
    /**
     * @template U of object
     * @param NumberRangesFilterOptions<U> $options
     * @return Numbers<U>
     */
    public static function create(
        string $dimension,
        NumberRangesFilterOptions $options,
        string $input,
    ): self {
        // strip all whitespace characters
        $input = preg_replace('/\s+/', '', $input);

        if ($input === null) {
            return new self(
                options: $options,
                numbers: [],
            );
        }

        $output = [];

        foreach (explode(',', $input) as $nums) {
            if (str_contains($nums, '-')) {
                $numberRange = NumberRange::create(
                    dimension: $dimension,
                    options: $options,
                    input: $nums,
                );

                if ($numberRange === null) {
                    continue;
                }

                $output[] = $numberRange;
            } else {
                $number = Number::create(
                    dimension: $dimension,
                    options: $options,
                    input: $nums,
                );

                if ($number === null) {
                    continue;
                }

                $output[] = $number;
            }
        }

        return new self(
            numbers: $output,
            options: $options,
        );
    }

    /**
     * @param list<Number<T>|NumberRange<T>> $numbers
     * @param NumberRangesFilterOptions<T> $options
     */
    private function __construct(
        private array $numbers,
        NumberRangesFilterOptions $options, // @phpstan-ignore constructor.unusedParameter
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return implode(',', array_map(
            static fn(Number|NumberRange $number): string => (string) $number,
            $this->numbers,
        ));
    }

    public function createExpression(): ?Expression
    {
        if ($this->numbers === []) {
            return null;
        }

        $expressions = [];

        foreach ($this->numbers as $number) {
            $expressions[] = $number->createExpression();
        }

        return Criteria::expr()->orX(...$expressions);
    }
}
