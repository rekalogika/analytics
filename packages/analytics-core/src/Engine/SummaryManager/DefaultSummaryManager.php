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

namespace Rekalogika\Analytics\Engine\SummaryManager;

use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Entity\PartitionRange;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentLock;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContextFactory;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\DirtySummaryEvent;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\NewDirtyFlagEvent;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class DefaultSummaryManager implements SummaryManager
{
    private ResultContextFactory $resultContextFactory;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private SummaryRefresherFactory $refresherFactory,
        private SourceEntitiesFactory $sourceEntitiesFactory,
        private RefreshAgentLock $refreshAgentLock,
        private int $queryResultLimit,
        private int $fillingNodesLimit,
        private HandlerFactory $handlerFactory,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->resultContextFactory = new ResultContextFactory(
            summaryMetadataFactory: $this->metadataFactory,
            managerRegistry: $this->managerRegistry,
            propertyAccessor: $this->propertyAccessor,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
            nodesLimit: $this->fillingNodesLimit,
            queryResultLimit: $this->queryResultLimit,
        );
    }

    #[\Override]
    public function refresh(
        string $class,
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void {
        if ($batchSize < 1) {
            throw new InvalidArgumentException(\sprintf(
                'Batch size must be at least 1, got %d.',
                $batchSize,
            ));
        }

        // wait for the refresh agent if it is refreshing the same summary,
        // concurrent refreshes of the same partition produce duplicate rows

        $this->refreshAgentLock->waitAndAcquire($class);

        try {
            $this->refresherFactory
                ->createSummaryRefresher($class)
                ->manualRefresh(
                    start: $start,
                    end: $end,
                    batchSize: $batchSize,
                    resumeId: $resumeId,
                );
        } finally {
            $this->refreshAgentLock->release($class);
        }
    }

    #[\Override]
    public function markDirty(string $class, iterable $keys): void
    {
        $partitionHandler = $this->handlerFactory
            ->getSummary($class)
            ->getPartition();

        $partitions = [];

        foreach ($keys as $key) {
            $partitions[] = $partitionHandler
                ->createLowestPartitionFromSourceValue($key);
        }

        $this->markPartitionsDirty($class, $partitions);
    }

    #[\Override]
    public function markDirtyRange(
        string $class,
        int|string $start,
        int|string $end,
    ): void {
        $partitionHandler = $this->handlerFactory
            ->getSummary($class)
            ->getPartition();

        $startPartition = $partitionHandler->createLowestPartitionFromSourceValue($start);
        $endPartition = $partitionHandler->createLowestPartitionFromSourceValue($end);

        if (PartitionUtil::isGreaterThan($startPartition, $endPartition)) {
            throw new InvalidArgumentException(\sprintf(
                'The start key "%s" must not be greater than the end key "%s".',
                $start,
                $end,
            ));
        }

        $this->markPartitionsDirty(
            $class,
            new PartitionRange($startPartition, $endPartition),
        );
    }

    /**
     * @param class-string $class
     * @param iterable<Partition> $partitions
     */
    private function markPartitionsDirty(string $class, iterable $partitions): void
    {
        $dirtyFlagsHandler = $this->handlerFactory
            ->getSummary($class)
            ->getDirtyFlags();

        /** @var array<string,DirtyFlag> */
        $dirtyFlags = [];

        foreach ($partitions as $partition) {
            $dirtyFlag = $dirtyFlagsHandler->createDirtyFlagForPartition($partition);
            $dirtyFlags[$dirtyFlag->getSignature()] = $dirtyFlag;
        }

        if ($dirtyFlags === []) {
            return;
        }

        $dirtyFlagsHandler->insertDirtyFlags($dirtyFlags);

        // the same events as the ones dispatched after a flush, the listener
        // of DirtySummaryEvent schedules the refresh agent

        foreach ($dirtyFlags as $dirtyFlag) {
            $this->eventDispatcher?->dispatch(new NewDirtyFlagEvent($dirtyFlag));
        }

        $this->eventDispatcher?->dispatch(new DirtySummaryEvent($class));
    }

    #[\Override]
    public function createQuery(
        ?int $queryResultLimit = null,
        ?int $fillingNodesLimit = null,
    ): DefaultQuery {
        $resultContextFactory = $this->resultContextFactory;

        if ($queryResultLimit !== null) {
            $resultContextFactory = $resultContextFactory
                ->withQueryResultLimit($queryResultLimit);
        }

        if ($fillingNodesLimit !== null) {
            $resultContextFactory = $resultContextFactory
                ->withNodesLimit($fillingNodesLimit);
        }

        return new DefaultQuery(
            summaryMetadataFactory: $this->metadataFactory,
            resultContextFactory: $resultContextFactory,
        );
    }
}
