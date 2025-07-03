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

namespace Rekalogika\Analytics\Time\Bin\IsoWeek;

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Time\Bin\Trait\RecurringTimeBinTrait;
use Rekalogika\Analytics\Time\Bin\Trait\RekalogikaTimeBinDQLExpressionTrait;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

enum IsoWeekWeekOfYear: int implements RecurringTimeBin
{
    use RecurringTimeBinTrait;
    use RekalogikaTimeBinDQLExpressionTrait;

    public const TYPE = Types::SMALLINT;

    case Week1 = 1;
    case Week2 = 2;
    case Week3 = 3;
    case Week4 = 4;
    case Week5 = 5;
    case Week6 = 6;
    case Week7 = 7;
    case Week8 = 8;
    case Week9 = 9;
    case Week10 = 10;
    case Week11 = 11;
    case Week12 = 12;
    case Week13 = 13;
    case Week14 = 14;
    case Week15 = 15;
    case Week16 = 16;
    case Week17 = 17;
    case Week18 = 18;
    case Week19 = 19;
    case Week20 = 20;
    case Week21 = 21;
    case Week22 = 22;
    case Week23 = 23;
    case Week24 = 24;
    case Week25 = 25;
    case Week26 = 26;
    case Week27 = 27;
    case Week28 = 28;
    case Week29 = 29;
    case Week30 = 30;
    case Week31 = 31;
    case Week32 = 32;
    case Week33 = 33;
    case Week34 = 34;
    case Week35 = 35;
    case Week36 = 36;
    case Week37 = 37;
    case Week38 = 38;
    case Week39 = 39;
    case Week40 = 40;
    case Week41 = 41;
    case Week42 = 42;
    case Week43 = 43;
    case Week44 = 44;
    case Week45 = 45;
    case Week46 = 46;
    case Week47 = 47;
    case Week48 = 48;
    case Week49 = 49;
    case Week50 = 50;
    case Week51 = 51;
    case Week52 = 52;
    case Week53 = 53;

    #[\Override]
    private static function getSqlToCharArgument(): string
    {
        return 'IW';
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return 'W' . $this->value;
    }
}
