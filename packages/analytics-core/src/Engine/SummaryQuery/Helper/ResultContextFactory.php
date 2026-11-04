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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCoordinates;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;
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

        $resultContextBuilder = new ResultContextBuilder(
            query: $query,
            metadata: $summaryMetadata,
            entityManager: $this->getEntityManager($summaryClass),
            propertyAccessor: $this->propertyAccessor,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
            resultContextFactory: $this,
            nodesLimit: $this->nodesLimit,
            queryResultLimit: $this->queryResultLimit,
        );

        return $resultContextBuilder->getResultContext();
    }

    /**
     * Returns the components of the query against the summary table that
     * computes the measures of the cell at the given coordinates, without
     * executing it. The query has no dimensions; the members of the
     * coordinates become conditions, so the query returns a single row,
     * comparable with the source query of the same coordinates. Returns null
     * if the summary table has no data yet.
     */
    public function createCoordinatesSummaryQueryComponents(
        DefaultCoordinates $coordinates,
    ): ?QueryComponents {
        $summaryClass = $coordinates->getSummaryClass();

        $query = new DefaultQuery(
            summaryMetadataFactory: $this->summaryMetadataFactory,
            resultContextFactory: $this,
        );

        $query->from($summaryClass);
        $query->dice($coordinates->getPredicate());

        foreach ($coordinates as $name => $dimension) {
            if ($name === '@values') {
                continue;
            }

            $query->andDice(Criteria::expr()->eq(
                $dimension->getName(),
                $dimension->getRawMember(),
            ));
        }

        $queryProcessor = new QueryProcessor(
            query: $query,
            metadata: $this->summaryMetadataFactory->getSummaryMetadata($summaryClass),
            entityManager: $this->getEntityManager($summaryClass),
            queryResultLimit: $this->queryResultLimit,
        );

        return $queryProcessor->getQueryComponents();
    }

    /**
     * @param class-string $summaryClass
     */
    private function getEntityManager(string $summaryClass): EntityManagerInterface
    {
        $entityManager = $this->managerRegistry->getManagerForClass($summaryClass);

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException(\sprintf(
                'No entity manager found for class "%s".',
                $summaryClass,
            ));
        }

        return $entityManager;
    }
}
