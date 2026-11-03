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

use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

/**
 * Dispatches the refresh agent of the summaries that have dirty flags but no
 * pending run. The refresh agent is normally dispatched after a flush that
 * creates dirty flags. That does not happen if the flags are created
 * otherwise, e.g. by a database trigger, or if the dispatched message is lost
 * or ends up in the failure transport. Without the sweeper, the flags would
 * wait until another flush creates flags for the same summary.
 *
 * A run is considered pending if it is scheduled in the future, or in the
 * past by less than the grace period, because the message may still be in
 * the queue. Dispatching a run that turns out to be a duplicate is harmless:
 * it waits for the refresh agent lock.
 */
final readonly class RefreshAgentSweeper
{
    /**
     * @param int|null $grace In seconds, how long a scheduled run may be
     * overdue before it is considered lost. Null means 0.
     */
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private HandlerFactory $handlerFactory,
        private RefreshAgentRunner $refreshAgentRunner,
        private RefreshAgentStrategy $refreshAgentStrategy = new DefaultRefreshAgentStrategy(),
        private ?int $grace = 300,
    ) {}

    public function run(RefreshAgentSweepCommand $command): void
    {
        $this->sweep();
    }

    /**
     * @return list<class-string> The summary classes whose refresh agent
     * has been dispatched.
     */
    public function sweep(): array
    {
        $now = new \DateTimeImmutable();
        $threshold = $now->sub(new \DateInterval(\sprintf('PT%dS', $this->grace ?? 0)));
        $dispatched = [];

        foreach ($this->summaryMetadataFactory->getSummaryClasses() as $summaryClass) {
            $summaryHandler = $this->handlerFactory->getSummary($summaryClass);

            $nextRefreshTime = $summaryHandler
                ->getDirtyFlags()
                ->getNextRefreshTime($this->refreshAgentStrategy, $now);

            // no dirty flags

            if ($nextRefreshTime === null) {
                continue;
            }

            $scheduledRefresh = $summaryHandler->getScheduledRefresh();

            if ($scheduledRefresh !== null && $scheduledRefresh > $threshold) {
                continue;
            }

            $this->refreshAgentRunner->scheduleAt(
                $summaryClass,
                max($nextRefreshTime, $now),
            );

            $dispatched[] = $summaryClass;
        }

        return $dispatched;
    }
}
