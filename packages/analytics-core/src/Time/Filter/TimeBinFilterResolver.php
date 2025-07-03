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

namespace Rekalogika\Analytics\Time\Filter;

use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\Bin\DayOfMonth;
use Rekalogika\Analytics\Time\Bin\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\DayOfYear;
use Rekalogika\Analytics\Time\Bin\Hour;
use Rekalogika\Analytics\Time\Bin\HourOfDay;
use Rekalogika\Analytics\Time\Bin\IsoWeek;
use Rekalogika\Analytics\Time\Bin\IsoWeekDate;
use Rekalogika\Analytics\Time\Bin\IsoWeekOfYear;
use Rekalogika\Analytics\Time\Bin\IsoWeekYear;
use Rekalogika\Analytics\Time\Bin\Month;
use Rekalogika\Analytics\Time\Bin\MonthOfYear;
use Rekalogika\Analytics\Time\Bin\Quarter;
use Rekalogika\Analytics\Time\Bin\QuarterOfYear;
use Rekalogika\Analytics\Time\Bin\WeekOfMonth;
use Rekalogika\Analytics\Time\Bin\Year;
use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;
use Rekalogika\Analytics\UX\PanelBundle\DimensionNotSupportedByFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange\DateRangeFilter;
use Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges\NumberRangesFilter;
use Rekalogika\Analytics\UX\PanelBundle\FilterResolver;
use Rekalogika\Analytics\UX\PanelBundle\FilterSpecification;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class TimeBinFilterResolver implements FilterResolver
{
    #[\Override]
    public function getFilterFactory(DimensionMetadata $dimension): FilterSpecification
    {
        $typeClass = $dimension->getTypeClass();
        $valueResolver = $dimension->getValueResolver();

        if (!$valueResolver instanceof TimeBinValueResolver) {
            throw new DimensionNotSupportedByFilter();
        }

        $typeClass = $valueResolver->getTypeClass();

        if (is_a($typeClass, Date::class, true)) {
            $filterClass = DateRangeFilter::class;

            $options = new TimeBinDateRangeFilterOptions(
                timeBinClass: $typeClass,
                label: $dimension->getLabel(),
                help: $this->getHelp($typeClass),
            );
        } else {
            $filterClass = NumberRangesFilter::class;

            $options = new TimeBinNumberRangeFilterOptions(
                timeBinClass: $typeClass,
                label: $dimension->getLabel(),
                help: $this->getHelp($typeClass),
            );
        }

        return new FilterSpecification($filterClass, $options);
    }


    /**
     * @param class-string<TimeBin> $class
     */
    private function getHelp(string $class): TranslatableInterface
    {
        return match ($class) {
            Hour::class => new TranslatableMessage('Example: 2024010107-2024033115,2024050111 (2024010107 means 1 January 2024, 07:00)'),
            HourOfDay::class => new TranslatableMessage('Example: 8-12,13-17'),

            Date::class => new TranslatableMessage('Example: 20240101-20240331,20240501 (20240101 means 1 January 2024)'),
            DayOfWeek::class => new TranslatableMessage('Example: 1,3-5 (1 is Monday, 7 is Sunday)'),
            DayOfMonth::class => new TranslatableMessage('Example: 1-5,10,15-20'),
            DayOfYear::class => new TranslatableMessage('Example: 1-90,100'),
            IsoWeekDate::class => new TranslatableMessage('Example: 2024021-2024032,2024041 (2024021 means 2024, week 2, Monday)'),

            IsoWeek::class => new TranslatableMessage('Example: 202402-202405,202514 (202402 means week 2 of 2024)'),
            WeekOfMonth::class => new TranslatableMessage('Example: 1-2,4'),
            IsoWeekOfYear::class => new TranslatableMessage('Example: 1-2,4'),

            Month::class => new TranslatableMessage('Example: 202401-202403,202501 (202401 means January 2024)'),
            MonthOfYear::class => new TranslatableMessage('Example: 1-3,5,7-12'),

            Quarter::class => new TranslatableMessage('Example: 20241-20243,20252 (20241 means 2024 Q1)'),
            QuarterOfYear::class => new TranslatableMessage('Example: 1-2,4'),

            Year::class => new TranslatableMessage('Example: 2020-2022,2024'),

            IsoWeekYear::class => new TranslatableMessage('Example: 2020-2022,2024'),
            default => throw new DimensionNotSupportedByFilter(),
        };
    }
}
