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

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Time\Bin\Trait\RecurringTimeBinTrait;
use Rekalogika\Analytics\Time\RecurringTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

enum MonthOfYear: int implements RecurringTimeBin
{
    use RecurringTimeBinTrait;

    public const string TYPE = Types::SMALLINT;

    case January = 1;
    case February = 2;
    case March = 3;
    case April = 4;
    case May = 5;
    case June = 6;
    case July = 7;
    case August = 8;
    case September = 9;
    case October = 10;
    case November = 11;
    case December = 12;

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $month = $this->name;
        $dateTime = (new \DateTimeImmutable('now'))
            ->setDate(2000, $this->value, 1);

        $locale = $translator->getLocale();

        $intlDateFormatter = new \IntlDateFormatter(
            locale: $locale,
            dateType: \IntlDateFormatter::FULL,
            timeType: \IntlDateFormatter::FULL,
            pattern: 'MMMM',
        );

        $formatted = $intlDateFormatter->format($dateTime);

        if (!\is_string($formatted)) {
            $formatted = $month;
        }

        return $formatted;
    }
}
