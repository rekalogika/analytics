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
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Represents a summary class
 */
final readonly class SummaryComponent
{
    public function __construct(
        private SummaryMetadata $summaryMetadata,
        private EntityManagerInterface $entityManager,
        private PropertyAccessorInterface $propertyAccessor,
    ) {}

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryMetadata->getSummaryClass();
    }

    public function getSource(): SourceOfSummaryComponent
    {
        return new SourceOfSummaryComponent(
            summaryMetadata: $this->summaryMetadata,
            entityManager: $this->entityManager,
        );
    }

    public function getPartition(): PartitionComponent
    {
        return new PartitionComponent(
            metadata: $this->summaryMetadata,
            propertyAccessor: $this->propertyAccessor,
        );
    }
}
