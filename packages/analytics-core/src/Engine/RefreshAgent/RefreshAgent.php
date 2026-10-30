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

namespace Rekalogika\Analytics\Engine\RefreshAgent;

use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;

final readonly class RefreshAgent
{
    public function __construct(
        private SummaryRefresherFactory $summaryRefresherFactory,
        private RefreshAgentLock $refreshAgentLock,
    ) {}

    public function run(RefreshAgentStartCommand $message): void
    {
        $class = $message->getSummaryClass();

        if ($this->refreshAgentLock->acquire($class) === false) {
            return;
        }

        $refreshAgentStrategy = new DefaultRefreshAgentStrategy(
            minimumAge: null,
            // @todo: probably we want to have enable idle delay handling. If there are idle items, another refresh
            // message with delayUntil the lowest idle item time should be dispatched.
            minimumIdleDelay: null,
        );

        try {
            $this->summaryRefresherFactory
                ->createSummaryRefresher($message->getSummaryClass())
                ->refresh($refreshAgentStrategy);
        } finally {
            $this->refreshAgentLock->release($class);
        }
    }
}
