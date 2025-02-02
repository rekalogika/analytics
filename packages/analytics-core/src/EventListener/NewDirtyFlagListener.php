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

namespace Rekalogika\Analytics\EventListener;

use Rekalogika\Analytics\RefreshWorker\RefreshScheduler;
use Rekalogika\Analytics\SummaryManager\Event\NewDirtyFlagEvent;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManagerRegistry;

final readonly class NewDirtyFlagListener
{
    /**
     * @param PartitionManagerRegistry $partitionManagerRegistry
     * @param RefreshScheduler<object> $refreshScheduler
     */
    public function __construct(
        private PartitionManagerRegistry $partitionManagerRegistry,
        private RefreshScheduler $refreshScheduler,
    ) {}

    public function onNewDirtyFlag(NewDirtyFlagEvent $event): void
    {
        $dirtyFlag = $event->getDirtyFlag();
        $class = $dirtyFlag->getClass();

        $partitionManager = $this->partitionManagerRegistry
            ->createPartitionManager($class);

        $partition = $partitionManager->getPartitionFromDirtyFlag($dirtyFlag);

        $this->refreshScheduler->scheduleWorker($class, $partition);
    }
}
