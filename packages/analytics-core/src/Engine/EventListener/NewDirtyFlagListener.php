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
use Rekalogika\Analytics\Engine\SummaryManager\Component\ComponentFactory;
use Rekalogika\Analytics\Engine\SummaryManager\Event\NewDirtyFlagEvent;

final readonly class NewDirtyFlagListener
{
    /**
     * @param RefreshScheduler<object> $refreshScheduler
     */
    public function __construct(
        private ComponentFactory $componentFactory,
        private RefreshScheduler $refreshScheduler,
    ) {}

    public function onNewDirtyFlag(NewDirtyFlagEvent $event): void
    {
        $dirtyFlag = $event->getDirtyFlag();
        $class = $dirtyFlag->getClass();

        $partitionComponent = $this->componentFactory
            ->getSummary($class)
            ->getPartition();

        $partition = $partitionComponent->getPartitionFromDirtyFlag($dirtyFlag);

        $this->refreshScheduler->scheduleWorker($class, $partition);
    }
}
