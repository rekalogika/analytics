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

namespace Rekalogika\Analytics\Time\Bin;

use Rekalogika\Analytics\Time\RecurringTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

enum HourOfDay: int implements RecurringTimeBin
{
    use RecurringTimeBinTrait;

    case Hour0 = 0;
    case Hour1 = 1;
    case Hour2 = 2;
    case Hour3 = 3;
    case Hour4 = 4;
    case Hour5 = 5;
    case Hour6 = 6;
    case Hour7 = 7;
    case Hour8 = 8;
    case Hour9 = 9;
    case Hour10 = 10;
    case Hour11 = 11;
    case Hour12 = 12;
    case Hour13 = 13;
    case Hour14 = 14;
    case Hour15 = 15;
    case Hour16 = 16;
    case Hour17 = 17;
    case Hour18 = 18;
    case Hour19 = 19;
    case Hour20 = 20;
    case Hour21 = 21;
    case Hour22 = 22;
    case Hour23 = 23;

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return \sprintf(
            '%02d:00-%02d:59',
            $this->value,
            $this->value,
        );
    }
}
