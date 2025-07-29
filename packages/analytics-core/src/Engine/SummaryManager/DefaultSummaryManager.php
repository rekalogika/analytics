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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Contracts\SourceResult;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SourceQuery;
use Rekalogika\Analytics\Engine\SummaryManager\SourceResult\DefaultSourceResult;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class DefaultSummaryManager implements SummaryManager
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private SummaryRefresherFactory $refresherFactory,
        private int $queryResultLimit,
        private int $fillingNodesLimit,
    ) {}

    #[\Override]
    public function refresh(
        string $class,
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void {
        $this->refresherFactory
            ->createSummaryRefresher($class)
            ->manualRefresh(
                start: $start,
                end: $end,
                batchSize: $batchSize,
                resumeId: $resumeId,
            );
    }

    #[\Override]
    public function createQuery(
        ?int $queryResultLimit = null,
        ?int $fillingNodesLimit = null,
    ): DefaultQuery {
        return new DefaultQuery(
            managerRegistry: $this->managerRegistry,
            summaryMetadataFactory: $this->metadataFactory,
            propertyAccessor: $this->propertyAccessor,
            queryResultLimit: $queryResultLimit ?? $this->queryResultLimit,
            fillingNodesLimit: $fillingNodesLimit ?? $this->fillingNodesLimit,
        );
    }

    #[\Override]
    public function getSource(Tuple $tuple): SourceResult
    {
        $summaryClass = $tuple->getSummaryClass();
        $metadata = $this->metadataFactory->getSummaryMetadata($summaryClass);
        $entityManager = $this->managerRegistry->getManagerForClass($summaryClass);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new LogicException(\sprintf(
                'The entity manager for class "%s" is not an instance of "EntityManagerInterface".',
                $summaryClass,
            ));
        }

        $sourceQuery = new SourceQuery(
            entityManager: $entityManager,
            summaryMetadata: $metadata,
        );

        $queryBuilder = $sourceQuery
            ->selectRoot()
            ->fromTuple($tuple)
            ->getQueryBuilder();

        return new DefaultSourceResult($queryBuilder);
    }

    public function getTupleQueryComponents(Tuple $tuple): QueryComponents
    {
        $summaryClass = $tuple->getSummaryClass();
        $metadata = $this->metadataFactory->getSummaryMetadata($summaryClass);
        $entityManager = $this->managerRegistry->getManagerForClass($summaryClass);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new LogicException(\sprintf(
                'The entity manager for class "%s" is not an instance of "EntityManagerInterface".',
                $summaryClass,
            ));
        }

        $sourceQuery = new SourceQuery(
            entityManager: $entityManager,
            summaryMetadata: $metadata,
        );

        return $sourceQuery
            ->selectMeasures()
            ->fromTuple($tuple)
            ->getQueryComponents();
    }
}
