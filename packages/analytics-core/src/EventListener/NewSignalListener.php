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
use Rekalogika\Analytics\SummaryManager\Event\NewSignalEvent;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManagerRegistry;

final readonly class NewSignalListener
{
    /**
     * @param PartitionManagerRegistry $partitionManagerRegistry
     * @param RefreshScheduler<object> $refreshScheduler
     */
    public function __construct(
        private PartitionManagerRegistry $partitionManagerRegistry,
        private RefreshScheduler $refreshScheduler,
    ) {}

    public function onNewSignal(NewSignalEvent $event): void
    {
        $signal = $event->getSignal();
        $class = $signal->getClass();

        $partitionManager = $this->partitionManagerRegistry
            ->createPartitionManager($class);

        $partition = $partitionManager->convertSignalToPartition($signal);

        $this->refreshScheduler->scheduleWorker($class, $partition);
    }
}
