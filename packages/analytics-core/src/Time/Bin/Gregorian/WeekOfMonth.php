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

namespace Rekalogika\Analytics\Time\Bin\Gregorian;

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Time\Bin\Trait\RecurringTimeBinTrait;
use Rekalogika\Analytics\Time\Bin\Trait\RekalogikaTimeBinDQLExpressionTrait;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Week of month. First week begins in 1st of the month. Note that a week is not
 * guaranteed to have 7 days, and not guaranteed to start on Monday or Sunday..
 * If it is important to have 7 days per week, use `MonthBasedWeekWeek` instead.
 */
enum WeekOfMonth: int implements RecurringTimeBin
{
    use RecurringTimeBinTrait;
    use RekalogikaTimeBinDQLExpressionTrait;

    public const TYPE = Types::SMALLINT;

    case Week1 = 1;
    case Week2 = 2;
    case Week3 = 3;
    case Week4 = 4;
    case Week5 = 5;

    #[\Override]
    private static function getSqlToCharArgument(): string
    {
        return 'W';
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return 'W' . $this->value;
    }
}
