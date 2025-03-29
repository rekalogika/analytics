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

namespace Rekalogika\Analytics\Model\TimeInterval;

use Rekalogika\Analytics\Contracts\Summary\RecurringTimeInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

enum QuarterOfYear: int implements RecurringTimeInterval
{
    use RecurringTimeIntervalTrait;

    case Q1 = 1;
    case Q2 = 2;
    case Q3 = 3;
    case Q4 = 4;

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->name;
    }
}
