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

namespace Rekalogika\Analytics\SummaryManager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManagerRegistry;

final readonly class SummaryRefresherFactory
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
        private PartitionManagerRegistry $partitionManagerRegistry,
        private DirtyFlagGenerator $dirtyFlagGenerator,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    /**
     * @param class-string $class
     */
    public function createSummaryRefresher(string $class): SummaryRefresher
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException(\sprintf('No entity manager found for class %s', $class));
        }

        $metadata = $this->metadataFactory->getSummaryMetadata($class);

        $partitionManager = $this->partitionManagerRegistry
            ->createPartitionManager($class);

        return new SummaryRefresher(
            entityManager: $entityManager,
            metadata: $metadata,
            partitionManager: $partitionManager,
            dirtyFlagGenerator: $this->dirtyFlagGenerator,
            eventDispatcher: $this->eventDispatcher,
        );
    }
}
