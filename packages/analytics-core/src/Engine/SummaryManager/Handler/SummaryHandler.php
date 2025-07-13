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

namespace Rekalogika\Analytics\Engine\SummaryManager\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\SummaryManager\PartitionRange;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SummaryPropertiesManager;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Represents a summary class
 */
final class SummaryHandler implements ResetInterface
{
    private readonly SourceOfSummaryHandler $sourceOfSummaryHandler;
    private readonly DirtyFlagsHandler $dirtyFlagsHandler;
    private readonly PartitionHandler $partitionHandler;

    private int|string|null $latestKey = null;

    public function __construct(
        private readonly SummaryMetadata $summaryMetadata,
        private readonly EntityManagerInterface $entityManager,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->sourceOfSummaryHandler = new SourceOfSummaryHandler(
            summaryMetadata: $this->summaryMetadata,
            entityManager: $this->entityManager,
        );

        $this->partitionHandler = new PartitionHandler(
            metadata: $this->summaryMetadata,
            propertyAccessor: $this->propertyAccessor,
        );

        $this->dirtyFlagsHandler = new DirtyFlagsHandler(
            metadata: $this->summaryMetadata,
            entityManager: $this->entityManager,
            partitionHandler: $this->partitionHandler,
        );
    }

    #[\Override]
    public function reset()
    {
        $this->sourceOfSummaryHandler->reset();
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryMetadata->getSummaryClass();
    }

    public function getSource(): SourceOfSummaryHandler
    {
        return $this->sourceOfSummaryHandler;
    }

    public function getPartition(): PartitionHandler
    {
        return $this->partitionHandler;
    }

    private function createSummaryPropertiesManager(): SummaryPropertiesManager
    {
        return new SummaryPropertiesManager(
            entityManager: $this->entityManager,
            summaryClass: $this->getSummaryClass(),
        );
    }

    /**
     * Gets the latest partitioning key that has been summarized
     */
    public function getLatestKey(): int|string|null
    {
        return $this->latestKey
            ??= $this->createSummaryPropertiesManager()->getMax();
    }

    /**
     * Updates the latest partitioning key that has been summarized.
     */
    public function updateLatestKey(int|string|null $max): void
    {
        $this->createSummaryPropertiesManager()->updateMax($max);
    }

    public function getDirtyFlags(): DirtyFlagsHandler
    {
        return $this->dirtyFlagsHandler;
    }


    /**
     * Gets the range of the new entities that are not yet summarized. Returns
     * null if there are no new entities.
     */
    public function getNewEntitiesRange(): ?PartitionRange
    {
        $summaryLatestKey = $this->getLatestKey();
        $sourceLatestKey = $this->getSource()->getLatestKey();

        if ($summaryLatestKey === null) {
            // if there are no record about the latest processed key, then we start
            // from the first key of the source

            $sourceEarliestKey = $this->getSource()->getEarliestKey();

            // if there is no earliest key in the source, then the source table
            // must be empty, so we return null

            if ($sourceEarliestKey === null) {
                return null;
            }

            $start = $this->getPartition()
                ->createLowestPartitionFromSourceValue($sourceEarliestKey);
        } elseif ($summaryLatestKey >= $sourceLatestKey) {
            // if the latest key in the summary is greater than or equal to the
            // latest key in the source, then there are no new entities to process,

            return null;
        } else {
            // if there is a record about the latest processed key, then we
            // start from there.

            $start = $this->getPartition()
                ->createLowestPartitionFromSourceValue($summaryLatestKey);
        }

        // this probably should not happen, but just in case

        if ($sourceLatestKey === null) {
            return null;
        }

        $end = $this->getPartition()
            ->createLowestPartitionFromSourceValue($sourceLatestKey);

        if (PartitionUtil::isGreaterThan($start, $end)) {
            return null;
        }

        return new PartitionRange($start, $end);
    }

    public function truncate(): void
    {
        $connection = $this->entityManager->getConnection();
        $metadata = $this->entityManager
            ->getClassMetadata($this->summaryMetadata->getSummaryClass());

        $tableName = $metadata->getTableName();

        $sql = \sprintf(
            "TRUNCATE TABLE %s",
            $tableName,
        );

        $statement = $connection->prepare($sql);
        $statement->executeStatement();

        $this->createSummaryPropertiesManager()->remove();
        $this->dirtyFlagsHandler->removeAllDirtyFlags();
    }
}
