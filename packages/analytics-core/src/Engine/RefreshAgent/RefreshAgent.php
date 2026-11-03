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
    /**
     * Delay in seconds before running again after a failed run.
     */
    private const RETRY_DELAY = 300;

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
            // created after it started, so we try again in 10 seconds. not
            // using `refresh()`, which would delay it to when the strategy
            // considers new dirty flags ready

            $this->refreshAgentRunner->scheduleAt(
                $class,
                new \DateTimeImmutable('+10 seconds'),
            );

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
        } catch (\Throwable $e) {
            // the remaining dirty flags would otherwise wait until new dirty
            // flags are created, so we schedule another run after a delay

            $this->scheduleRetry($class);

            throw $e;
        } finally {
            $this->refreshAgentLock->release($class);
        }
    }

    /**
     * Schedules another run after a failed run. A failure here is ignored, so
     * that it does not replace the exception that caused the run to fail.
     *
     * @param class-string $class
     */
    private function scheduleRetry(string $class): void
    {
        try {
            $this->refreshAgentRunner->scheduleAt(
                $class,
                new \DateTimeImmutable(\sprintf('+%d seconds', self::RETRY_DELAY)),
            );
        } catch (\Throwable) {
            // ignored, the messenger retry or new dirty flags will dispatch
            // the refresh agent again
        }
    }
}
