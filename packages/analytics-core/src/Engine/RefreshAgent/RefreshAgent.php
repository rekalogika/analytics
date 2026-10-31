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
        private RefreshAgentRunner $refreshAgentRunner,
        private RefreshAgentStrategy $refreshAgentStrategy = new DefaultRefreshAgentStrategy(),
    ) {}

    public function run(RefreshAgentStartCommand $message): void
    {
        $class = $message->getSummaryClass();

        if ($this->refreshAgentLock->acquire($class) === false) {
            // another refresh is running. it might have missed the dirty flags
            // created after it started, so we try again later

            $this->refreshAgentRunner->refresh($class, minimumDelay: 10);

            return;
        }

        try {
            $summaryRefresher = $this->summaryRefresherFactory
                ->createSummaryRefresher($class);

            $summaryRefresher->refresh($this->refreshAgentStrategy);

            // some dirty partitions might not be ready to be refreshed yet,
            // schedule another run when the earliest of them will be

            $nextRefreshTime = $summaryRefresher
                ->getNextRefreshTime($this->refreshAgentStrategy);

            if ($nextRefreshTime !== null) {
                $this->refreshAgentRunner->scheduleAt($class, $nextRefreshTime);
            }
        } finally {
            $this->refreshAgentLock->release($class);
        }
    }
}
