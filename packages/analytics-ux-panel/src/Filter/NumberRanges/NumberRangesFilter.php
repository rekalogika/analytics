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
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @template T of object
 */
final class NumberRangesFilter implements Filter
{
    private readonly string $rawValue;

    private string $value = '';

    /**
     * @var list<NumberRange<T>|Number<T>>|null
     */
    private ?array $numbers = null;

    /**
     * @param NumberRangesFilterOptions<T> $options
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly NumberRangesFilterOptions $options,
        private readonly string $dimension,
        array $inputArray,
    ) {
        /** @psalm-suppress MixedAssignment */
        $rawValue = $inputArray['value'] ?? '';

        if (!\is_string($rawValue)) {
            $rawValue = '';
        }

        $this->rawValue = $rawValue;
    }

    #[\Override]
    public function getTemplate(): string
    {
        return '@RekalogikaAnalyticsUXPanel/filter/number_ranges_filter.html.twig';
    }

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension;
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->options->getLabel();
    }

    public function getHelp(): ?TranslatableInterface
    {
        return $this->options->getHelp();
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    public function getValue(): string
    {
        if ($this->value !== '') {
            return $this->value;
        }

        return $this->value = implode(',', array_map(
            static fn(Number|NumberRange $number): string => (string) $number,
            $this->getNumbers(),
        ));
    }

    /**
     * @return list<Number<T>|NumberRange<T>>
     */
    public function getNumbers(): array
    {
        if ($this->numbers !== null) {
            return $this->numbers;
        }

        $input = str_replace(' ', '', $this->rawValue); // strip out spaces
        $output = [];

        foreach (explode(',', $input) as $nums) {
            if (str_contains($nums, '-')) {
                [$start, $end] = explode('-', $nums);

                if (!is_numeric($start) || !is_numeric($end)) {
                    continue;
                }

                $start = (int) $start;
                $end = (int) $end;

                $output[] = new NumberRange(
                    dimension: $this->dimension,
                    options: $this->options,
                    start: $start,
                    end: $end,
                );
            } else {
                if (!is_numeric($nums)) {
                    continue;
                }

                $nums = (int) $nums;

                $output[] = new Number(
                    dimension: $this->dimension,
                    options: $this->options,
                    number: $nums,
                );
            }
        }

        return $this->numbers = $output;
    }

    #[\Override]
    public function createExpression(): ?Expression
    {
        $numbers = $this->getNumbers();

        if ($numbers === []) {
            return null;
        }

        $expressions = [];

        foreach ($numbers as $number) {
            $expressions[] = $number->createExpression();
        }

        return Criteria::expr()->orX(...$expressions);
    }
}
