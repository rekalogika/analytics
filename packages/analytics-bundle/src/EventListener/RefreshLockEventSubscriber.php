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

namespace Rekalogika\Analytics\Bundle\EventListener;

use Rekalogika\Analytics\Engine\RefreshAgent\SymfonyRefreshAgentLock;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\AbstractStartEvent;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\DeleteRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\RefreshRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\RollUpRangeStartEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extends the lifetime of the refresh lock before each step of a refresh, so
 * that the lock only expires if a single step takes longer than its TTL.
 */
final readonly class RefreshLockEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SymfonyRefreshAgentLock $refreshAgentLock,
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            RefreshRangeStartEvent::class => 'onStartEvent',
            DeleteRangeStartEvent::class => 'onStartEvent',
            RollUpRangeStartEvent::class => 'onStartEvent',
        ];
    }

    public function onStartEvent(AbstractStartEvent $event): void
    {
        $this->refreshAgentLock->refresh($event->getClass());
    }
}
