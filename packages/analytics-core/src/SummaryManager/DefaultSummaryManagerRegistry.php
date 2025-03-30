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
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Contracts\SummaryManagerRegistry;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class DefaultSummaryManagerRegistry implements SummaryManagerRegistry
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private SummaryRefresherFactory $refresherFactory,
        private DistinctValuesResolver $distinctValuesResolver,
        private int $queryResultLimit,
    ) {}

    #[\Override]
    public function getManager(string $class): SummaryManager
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new UnexpectedValueException('Entity manager not found for class ' . $class);
        }

        $summaryMetadata = $this->metadataFactory->getSummaryMetadata($class);

        return new DefaultSummaryManager(
            class: $class,
            entityManager: $entityManager,
            metadata: $summaryMetadata,
            propertyAccessor: $this->propertyAccessor,
            refresherFactory: $this->refresherFactory,
            distinctValuesResolver: $this->distinctValuesResolver,
            queryResultLimit: $this->queryResultLimit,
        );
    }
}
