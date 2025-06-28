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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Time\Bin\DayOfMonth;
use Rekalogika\Analytics\Time\Bin\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\DayOfYear;
use Rekalogika\Analytics\Time\Bin\HourOfDay;
use Rekalogika\Analytics\Time\Bin\Month;
use Rekalogika\Analytics\Time\Bin\MonthOfYear;
use Rekalogika\Analytics\Time\Bin\Quarter;
use Rekalogika\Analytics\Time\Bin\QuarterOfYear;
use Rekalogika\Analytics\Time\Bin\Week;
use Rekalogika\Analytics\Time\Bin\WeekDate;
use Rekalogika\Analytics\Time\Bin\WeekOfMonth;
use Rekalogika\Analytics\Time\Bin\WeekOfYear;
use Rekalogika\Analytics\Time\Bin\WeekYear;
use Rekalogika\Analytics\Time\Bin\Year;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Model\Number;
use Rekalogika\Analytics\UX\PanelBundle\Filter\Model\NumberRange;
use Symfony\Contracts\Translation\TranslatableInterface;

final class NumberRangesFilter implements Filter
{
    private readonly string $rawValue;

    private string $value = '';

    /**
     * @var list<NumberRange|Number>|null
     */
    private ?array $numbers = null;

    /**
     * @param class-string<TimeBin|RecurringTimeBin> $typeClass
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly TranslatableInterface $label,
        private readonly string $dimension,
        private readonly string $typeClass,
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
        return $this->label;
    }

    public function getHelp(): ?TranslatableInterface
    {
        return match ($this->typeClass) {
            DayOfMonth::class => new TranslatableMessage('Example: 1-5,10,15-20'),
            DayOfWeek::class => new TranslatableMessage('Example: 1,3-5 (1 is Monday, 7 is Sunday)'),
            DayOfYear::class => new TranslatableMessage('Example: 1-90,100'),
            HourOfDay::class => new TranslatableMessage('Example: 8-12,13-17'),
            MonthOfYear::class => new TranslatableMessage('Example: 1-3,5,7-12'),
            QuarterOfYear::class => new TranslatableMessage('Example: 1-2,4'),
            WeekOfMonth::class => new TranslatableMessage('Example: 1-2,4'),
            WeekOfYear::class => new TranslatableMessage('Example: 1-2,4'),
            Year::class => new TranslatableMessage('Example: 2020-2022,2024'),
            WeekYear::class => new TranslatableMessage('Example: 2020-2022,2024'),
            WeekDate::class => new TranslatableMessage('Example: 2024021-2024032,2024041 (2024021 means 2024, week 2, Monday)'),
            default => null,
            Quarter::class => new TranslatableMessage('Example: 20241-20243,20252 (20241 means 2024 Q1)'),
            Month::class => new TranslatableMessage('Example: 202401-202403,202501 (202401 means January 2024)'),
            Week::class => new TranslatableMessage('Example: 202402-202405,202514 (202402 means week 2 of 2024)'),
        };
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
     * @return list<Number|NumberRange>
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
                    typeClass: $this->typeClass,
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
                    typeClass: $this->typeClass,
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
