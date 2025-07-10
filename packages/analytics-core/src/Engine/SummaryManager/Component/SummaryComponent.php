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

namespace Rekalogika\Analytics\Engine\SummaryManager\Component;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SummaryPropertiesManager;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Represents a summary class
 */
final readonly class SummaryComponent implements ResetInterface
{
    private SourceOfSummaryComponent $sourceOfSummaryComponent;

    public function __construct(
        private SummaryMetadata $summaryMetadata,
        private EntityManagerInterface $entityManager,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->sourceOfSummaryComponent = new SourceOfSummaryComponent(
            summaryMetadata: $this->summaryMetadata,
            entityManager: $this->entityManager,
        );
    }

    #[\Override]
    public function reset()
    {
        $this->sourceOfSummaryComponent->reset();
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryMetadata->getSummaryClass();
    }

    public function getSource(): SourceOfSummaryComponent
    {
        return $this->sourceOfSummaryComponent;
    }

    public function getPartition(): PartitionComponent
    {
        return new PartitionComponent(
            metadata: $this->summaryMetadata,
            propertyAccessor: $this->propertyAccessor,
        );
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
        return $this->createSummaryPropertiesManager()->getMax();
    }

    /**
     * Updates the latest partitioning key that has been summarized.
     */
    public function updateLatestKey(int|string|null $max): void
    {
        $this->createSummaryPropertiesManager()->updateMax($max);
    }
}
