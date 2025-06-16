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

namespace Rekalogika\Analytics\Time;

enum TimeBinType: string
{
    case Hour = 'hour';
    case HourOfDay = 'hourOfDay';

    case Date = 'date';
    case DayOfWeek = 'dayOfWeek';
    case DayOfMonth = 'dayOfMonth';
    case DayOfYear = 'dayOfYear';

    case Week = 'week';
    case WeekDate = 'weekDate';
    case WeekYear = 'weekYear';
    case WeekOfYear = 'weekOfYear';
    case WeekOfMonth = 'weekOfMonth';

    case Month = 'month';
    case MonthOfYear = 'monthOfYear';

    case Quarter = 'quarter';
    case QuarterOfYear = 'quarterOfYear';

    case Year = 'year';
}
