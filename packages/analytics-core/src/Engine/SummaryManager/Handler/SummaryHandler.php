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
use Rekalogika\Analytics\Engine\SummaryManager\Query\SummaryPropertiesManager;
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
}
