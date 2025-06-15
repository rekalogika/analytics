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

namespace Rekalogika\Analytics\Engine\SummaryManager\Event;

final readonly class RefreshRangeStartEvent extends AbstractStartEvent
{
    #[\Override]
    public function __toString(): string
    {
        return 'Refresh range start';
    }

    #[\Override]
    public function getEventId(): string
    {
        return 'RefreshRange';
    }
}
