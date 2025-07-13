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

/**
 * Calls refresh agent dispatcher to run the refresh agent.
 */
final readonly class RefreshAgentRunner
{
    public function __construct(
        private RefreshAgentDispatcher $refreshAgentDispatcher,
        private RefreshAgentLock $refreshAgentLock,
    ) {}

    /**
     * @param class-string $summaryClass
     */
    public function refresh(string $summaryClass): void
    {
        if ($this->refreshAgentLock->acquire($summaryClass) === false) {
            return;
        }

        $this->refreshAgentLock->release($summaryClass);

        $command = new RefreshAgentStartCommand($summaryClass);
        $this->refreshAgentDispatcher->dispatch($command, new \DateTimeImmutable('now + 1 second'));
    }
}
