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

namespace Rekalogika\Analytics\Engine\EventListener;

use Rekalogika\Analytics\Engine\RefreshWorker\RefreshScheduler;
use Rekalogika\Analytics\Engine\SummaryManager\Event\NewDirtyFlagEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\HandlerFactory;

final readonly class NewDirtyFlagListener
{
    /**
     * @param RefreshScheduler<object> $refreshScheduler
     */
    public function __construct(
        private HandlerFactory $handlerFactory,
        private RefreshScheduler $refreshScheduler,
    ) {}

    public function onNewDirtyFlag(NewDirtyFlagEvent $event): void
    {
        $dirtyFlag = $event->getDirtyFlag();
        $class = $dirtyFlag->getClass();

        $partitionHandler = $this->handlerFactory
            ->getSummary($class)
            ->getPartition();

        $partition = $partitionHandler->getPartitionFromDirtyFlag($dirtyFlag);

        $this->refreshScheduler->scheduleWorker($class, $partition);
    }
}
