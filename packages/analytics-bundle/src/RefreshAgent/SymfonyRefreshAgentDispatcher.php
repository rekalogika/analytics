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
        $envelope = new Envelope($command, [
            DelayStamp::delayUntil($runAt),
        ]);

        $this->messageBus->dispatch($envelope);
    }
}
