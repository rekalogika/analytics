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

namespace Rekalogika\Analytics\TimeInterval\Types;

use Rekalogika\Analytics\TimeInterval\Week;

final class WeekType extends TimeIntervalType
{
    use IntegerTypeTrait;

    #[\Override]
    protected function getClass(): string
    {
        return Week::class;
    }

    final public function getName(): string
    {
        return 'rekalogika_analytics_week';
    }
}
