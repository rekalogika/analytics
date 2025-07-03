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

enum QuarterOfYear: int implements RecurringTimeBin
{
    use RecurringTimeBinTrait;
    use RekalogikaTimeBinDQLExpressionTrait;

    public const TYPE = Types::SMALLINT;

    case Q1 = 1;
    case Q2 = 2;
    case Q3 = 3;
    case Q4 = 4;

    #[\Override]
    private static function getSqlToCharArgument(): string
    {
        return 'Q';
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->name;
    }
}
