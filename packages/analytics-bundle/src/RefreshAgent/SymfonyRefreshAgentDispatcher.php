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

namespace Rekalogika\Analytics\Bundle\RefreshAgent;

use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentDispatcher;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStartCommand;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final readonly class SymfonyRefreshAgentDispatcher implements RefreshAgentDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    #[\Override]
    public function dispatch(
        RefreshAgentStartCommand $command,
        \DateTimeInterface $runAt,
    ): void {
        // not using DelayStamp::delayUntil() because its reference time
        // differs between Symfony versions: wall clock before 8.1,
        // Clock::get() since 8.1. $runAt is always based on the wall clock.
        $delay = (int) round(((float) $runAt->format('U.u') - microtime(true)) * 1000.0);

        $envelope = new Envelope($command, [
            new DelayStamp(max(0, $delay)),
        ]);

        $this->messageBus->dispatch($envelope);
    }
}
