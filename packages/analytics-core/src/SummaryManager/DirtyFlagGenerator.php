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
use Rekalogika\Analytics\Model\Entity\DirtyFlag;
use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManagerRegistry;

/**
 * Generates dirty flags to indicate that a summary needs to be refreshed.
 */
final readonly class DirtyFlagGenerator
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private PartitionManagerRegistry $partitionManagerRegistry,
    ) {}

    /**
     * @return iterable<DirtyFlag>
     */
    public function generateForEntityCreation(object $entity): iterable
    {
        $sourceMetadata = $this->summaryMetadataFactory
            ->getSourceMetadata($entity::class);

        $summaryClasses = $sourceMetadata->getAllInvolvedSummaryClasses();

        foreach ($summaryClasses as $summaryClass) {
            yield $this->createNewFlag($summaryClass);
        }
    }

    /**
     * @return iterable<DirtyFlag>
     */
    public function generateForEntityDeletion(object $entity): iterable
    {
        $sourceMetadata = $this->summaryMetadataFactory
            ->getSourceMetadata($entity::class);

        $summaryClasses = $sourceMetadata->getAllInvolvedSummaryClasses();

        foreach ($summaryClasses as $summaryClass) {
            $partitionManager = $this->partitionManagerRegistry
                ->createPartitionManager($summaryClass);

            $partition = $partitionManager->getLowestPartitionFromEntity($entity);
            yield $this->createDirtyFlag($summaryClass, $partition);
        }
    }

    /**
     * @param list<string> $modifiedProperties
     * @return iterable<DirtyFlag>
     */
    public function generateForEntityModification(
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
            yield $this->createDirtyFlag($summaryClass, $partition);
        }
    }

    /**
     * @param class-string $class
     */
    public function createDirtyFlag(
        string $class,
        Partition $partition,
    ): DirtyFlag {
        return new DirtyFlag(
            class: $class,
            level: $partition->getLevel(),
            key: (string) $partition->getKey(),
        );
    }

    /**
     * @param class-string $class
     */
    private function createNewFlag(string $class): DirtyFlag
    {
        return new DirtyFlag(
            class: $class,
            level: null,
            key: null,
        );
    }
}
