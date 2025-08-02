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

namespace Rekalogika\Analytics\Engine\EventListener;

use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentRunner;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\DirtySummaryEvent;

final readonly class DirtySummaryEventListener
{
    public function __construct(
        private RefreshAgentRunner $refreshAgentRunner,
    ) {}

    public function onDirtySummaryEvent(DirtySummaryEvent $event): void
    {
        $this->refreshAgentRunner->refresh($event->getSummaryClass());
    }
}
