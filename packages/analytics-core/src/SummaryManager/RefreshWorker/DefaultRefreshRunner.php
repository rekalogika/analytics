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

namespace Rekalogika\Analytics\SummaryManager\RefreshWorker;

use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\RefreshWorker\RefreshRunner;
use Rekalogika\Analytics\SummaryManager\Event\NewDirtyFlagEvent;
use Rekalogika\Analytics\SummaryManager\SummaryRefresherFactory;

final readonly class DefaultRefreshRunner implements RefreshRunner
{
    public function __construct(
        private SummaryRefresherFactory $summaryRefresherFactory,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function refresh(string $class, ?Partition $partition): void
    {
        $summaryRefresher = $this->summaryRefresherFactory
            ->createSummaryRefresher($class);

        if ($partition !== null) {
            $dirtyFlags = $summaryRefresher->refreshPartition($partition);
        } else {
            $dirtyFlags = $summaryRefresher->convertNewRecordsToDirtyFlags();
        }

        foreach ($dirtyFlags as $dirtyFlag) {
            $event = new NewDirtyFlagEvent($dirtyFlag);
            $this->eventDispatcher?->dispatch($event);
        }
    }
}
