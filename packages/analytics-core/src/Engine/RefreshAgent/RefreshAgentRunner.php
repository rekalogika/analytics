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
use Rekalogika\Analytics\Engine\Util\RefreshAgentStrategyUtil;

/**
 * Calls refresh agent dispatcher to run the refresh agent. Keeps track of the
 * scheduled run of each summary class, so that the refresh agent is not
 * dispatched more often than necessary.
 */
final readonly class RefreshAgentRunner
{
    public function __construct(
        private RefreshAgentDispatcher $refreshAgentDispatcher,
        private HandlerFactory $handlerFactory,
        private RefreshAgentStrategy $refreshAgentStrategy = new DefaultRefreshAgentStrategy(),
    ) {}

    /**
     * Makes sure the refresh agent will run for the summary class. Called
     * after new dirty flags are created.
     *
     * The new dirty flags won't be ready to be refreshed until the time
     * determined by the strategy. If the refresh agent is already scheduled
     * to run before that, it does nothing. The scheduled run will process the
     * new dirty flags, or reschedule itself if they are not ready yet.
     *
     * @param class-string $summaryClass
     * @param int $minimumDelay The minimum delay in seconds
     */
    public function refresh(string $summaryClass, int $minimumDelay = 1): void
    {
        $now = new \DateTimeImmutable();

        $runAt = max(
            $now->add(new \DateInterval(\sprintf('PT%dS', $minimumDelay))),
            RefreshAgentStrategyUtil::getRefreshTime(
                refreshAgentStrategy: $this->refreshAgentStrategy,
                earliest: $now,
                latest: $now,
            ),
        );

        $this->scheduleAt($summaryClass, $runAt);
    }

    /**
     * Makes sure the refresh agent will run for the summary class, at or
     * before the specified time.
     *
     * @param class-string $summaryClass
     */
    public function scheduleAt(string $summaryClass, \DateTimeImmutable $runAt): void
    {
        $now = new \DateTimeImmutable();
        $scheduledRefresh = $this->handlerFactory
            ->getSummary($summaryClass)
            ->getScheduledRefresh();

        if (
            $scheduledRefresh !== null
            && $scheduledRefresh > $now
            && $scheduledRefresh <= $runAt
        ) {
            return;
        }

        // at least one second in the future, to prevent busy loops

        $this->dispatch(
            $summaryClass,
            max($runAt, $now->modify('+1 second')),
        );
    }

    /**
     * @param class-string $summaryClass
     */
    private function dispatch(string $summaryClass, \DateTimeImmutable $runAt): void
    {
        $command = new RefreshAgentStartCommand($summaryClass);
        $this->refreshAgentDispatcher->dispatch($command, $runAt);

        // recorded after the dispatch, so that the recorded time always
        // refers to a dispatched run

        $this->handlerFactory
            ->getSummary($summaryClass)
            ->updateScheduledRefresh($runAt);
    }
}
