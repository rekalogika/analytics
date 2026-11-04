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

namespace Rekalogika\Analytics\Tests\App\Entity;

use Rekalogika\Analytics\Numeric\Bin\Trait\RangeBinTrait;
use Rekalogika\Analytics\Numeric\RangeBin;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * String-backed, and the alphabetical order of the values differs from the
 * order of the cases, to test that the bins are sorted by the order of the
 * cases.
 */
enum AgeGroup: string implements RangeBin, TranslatableInterface
{
    use RangeBinTrait;

    case UpTo25 = 'up-to-25';
    case From26To35 = '26-to-35';
    case From36To45 = '36-to-45';
    case From46To55 = '46-to-55';
    case From56To65 = '56-to-65';
    case Over65 = 'over-65';

    #[\Override]
    public static function getThresholds(): array
    {
        return [26, 36, 46, 56, 66];
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::UpTo25 => $translator->trans('Up to 25', locale: $locale),
            self::From26To35 => $translator->trans('26 to 35', locale: $locale),
            self::From36To45 => $translator->trans('36 to 45', locale: $locale),
            self::From46To55 => $translator->trans('46 to 55', locale: $locale),
            self::From56To65 => $translator->trans('56 to 65', locale: $locale),
            self::Over65 => $translator->trans('Over 65', locale: $locale),
        };
    }
}
