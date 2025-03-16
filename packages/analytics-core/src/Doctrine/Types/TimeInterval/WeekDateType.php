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

namespace Rekalogika\Analytics\Doctrine\Types\TimeInterval;

use Rekalogika\Analytics\Model\TimeInterval\WeekDate;

final class WeekDateType extends TimeIntervalType
{
    use IntegerTypeTrait;

    #[\Override]
    protected function getClass(): string
    {
        return WeekDate::class;
    }

    final public function getName(): string
    {
        return 'rekalogika_analytics_week_date';
    }
}
