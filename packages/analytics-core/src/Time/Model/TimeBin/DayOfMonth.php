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

namespace Rekalogika\Analytics\Time\Model\TimeBin;

use Rekalogika\Analytics\Time\RecurringTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DayOfMonth: int implements RecurringTimeBin
{
    use RecurringTimeBinTrait;

    case Day1 = 1;
    case Day2 = 2;
    case Day3 = 3;
    case Day4 = 4;
    case Day5 = 5;
    case Day6 = 6;
    case Day7 = 7;
    case Day8 = 8;
    case Day9 = 9;
    case Day10 = 10;
    case Day11 = 11;
    case Day12 = 12;
    case Day13 = 13;
    case Day14 = 14;
    case Day15 = 15;
    case Day16 = 16;
    case Day17 = 17;
    case Day18 = 18;
    case Day19 = 19;
    case Day20 = 20;
    case Day21 = 21;
    case Day22 = 22;
    case Day23 = 23;
    case Day24 = 24;
    case Day25 = 25;
    case Day26 = 26;
    case Day27 = 27;
    case Day28 = 28;
    case Day29 = 29;
    case Day30 = 30;
    case Day31 = 31;

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return (string) $this->value;
    }
}
