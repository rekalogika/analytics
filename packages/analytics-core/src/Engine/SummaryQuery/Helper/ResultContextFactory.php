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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class ResultContextFactory
{
    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
        private readonly ManagerRegistry $managerRegistry,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly SourceEntitiesFactory $sourceEntitiesFactory,
        private int $nodesLimit,
        private int $queryResultLimit,
    ) {}

    public function withNodesLimit(int $nodesLimit): self
    {
        return new self(
            summaryMetadataFactory: $this->summaryMetadataFactory,
            managerRegistry: $this->managerRegistry,
            propertyAccessor: $this->propertyAccessor,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
            nodesLimit: $nodesLimit,
            queryResultLimit: $this->queryResultLimit,
        );
    }

    public function withQueryResultLimit(int $queryResultLimit): self
    {
        return new self(
            summaryMetadataFactory: $this->summaryMetadataFactory,
            managerRegistry: $this->managerRegistry,
            propertyAccessor: $this->propertyAccessor,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
            nodesLimit: $this->nodesLimit,
            queryResultLimit: $queryResultLimit,
        );
    }

    public function createResultContext(DefaultQuery $query): ResultContext
    {
        $summaryClass = $query->getFrom();

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $entityManager = $this->managerRegistry->getManagerForClass($summaryClass);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException(\sprintf(
                'No entity manager found for class "%s".',
                $summaryClass,
            ));
        }

        $resultContextBuilder = new ResultContextBuilder(
            query: $query,
            metadata: $summaryMetadata,
            entityManager: $entityManager,
            propertyAccessor: $this->propertyAccessor,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
            resultContextFactory: $this,
            nodesLimit: $this->nodesLimit,
            queryResultLimit: $this->queryResultLimit,
        );

        return $resultContextBuilder->getResultContext();
    }
}
