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

use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\Model\Entity\SummarySignal;
use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManagerRegistry;

final readonly class SummarySignalManager
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private PartitionManagerRegistry $partitionManagerRegistry,
    ) {}

    /**
     * @return iterable<SummarySignal>
     */
    public function generateSignalsForSourceEntityCreation(object $entity): iterable
    {
        $sourceMetadata = $this->summaryMetadataFactory
            ->getSourceMetadata($entity::class);

        $summaryClasses = $sourceMetadata->getAllInvolvedSummaryClasses();

        foreach ($summaryClasses as $summaryClass) {
            yield $this->createNewRecordsSignal($summaryClass);
        }
    }

    /**
     * @return iterable<SummarySignal>
     */
    public function generateSignalsForSourceEntityDeletion(object $entity): iterable
    {
        $sourceMetadata = $this->summaryMetadataFactory
            ->getSourceMetadata($entity::class);

        $summaryClasses = $sourceMetadata->getAllInvolvedSummaryClasses();

        foreach ($summaryClasses as $summaryClass) {
            $partitionManager = $this->partitionManagerRegistry
                ->createPartitionManager($summaryClass);

            $partition = $partitionManager->getLowestPartitionFromEntity($entity);
            yield $this->createDirtyPartitionSignal($summaryClass, $partition);
        }
    }

    /**
     * @param list<string> $modifiedProperties
     * @return iterable<SummarySignal>
     */
    public function generateSignalsForSourceEntityModification(
        object $entity,
        array $modifiedProperties,
    ): iterable {
        $sourceMetadata = $this->summaryMetadataFactory
            ->getSourceMetadata($entity::class);

        $summaryClasses = $sourceMetadata
            ->getInvolvedSummaryClassesByChangedProperties($modifiedProperties);

        foreach ($summaryClasses as $summaryClass) {
            $partitionManager = $this->partitionManagerRegistry
                ->createPartitionManager($summaryClass);

            $partition = $partitionManager->getLowestPartitionFromEntity($entity);
            yield $this->createDirtyPartitionSignal($summaryClass, $partition);
        }
    }

    /**
     * @param class-string $class
     */
    private function createDirtyPartitionSignal(
        string $class,
        Partition $partition,
    ): SummarySignal {
        return new SummarySignal(
            class: $class,
            level: $partition->getLevel(),
            key: (string) $partition->getKey(),
        );
    }

    /**
     * @param class-string $class
     */
    private function createNewRecordsSignal(string $class): SummarySignal
    {
        return new SummarySignal(
            class: $class,
            level: null,
            key: null,
        );
    }
}
