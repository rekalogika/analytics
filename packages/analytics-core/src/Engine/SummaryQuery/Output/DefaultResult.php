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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\EmptyResult;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContextBuilder;
use Rekalogika\Analytics\Engine\SummaryQuery\Query\LowestPartitionLastIdQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Query\SummaryQuery;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @internal
 */
final class DefaultResult implements Result
{
    private SummaryQuery|EmptyResult|null $summarizerQuery = null;

    private QueryComponents|EmptyResult|null $queryComponents = null;

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $queryResult = null;

    private ?DefaultTable $table = null;

    // private ?bool $hasHierarchicalOrdering = null;

    private ?ResultContext $resultContext = null;

    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private readonly TranslatableInterface $label,
        private readonly string $summaryClass,
        private readonly DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly SourceEntitiesFactory $sourceEntitiesFactory,
        private int $nodesLimit,
        private int $queryResultLimit,
    ) {}

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function getQueryResult(): array
    {
        if ($this->queryResult !== null) {
            return $this->queryResult;
        }

        $summarizerQuery = $this->getSummaryQuery();

        if ($summarizerQuery === null) {
            return $this->queryResult = [];
        }

        return $this->queryResult = $summarizerQuery->getQueryResult();
    }

    private function getSummaryQuery(): ?SummaryQuery
    {
        if ($this->summarizerQuery !== null) {
            if ($this->summarizerQuery instanceof EmptyResult) {
                return null;
            }

            return $this->summarizerQuery;
        }

        // get max id
        $lastId = $this->getLowestPartitionLastId();

        // if max id is null, no data exists in the summary table
        if ($lastId === null) {
            $this->summarizerQuery = new EmptyResult();
            return null;
        }

        return $this->summarizerQuery = new SummaryQuery(
            entityManager: $this->entityManager,
            query: $this->query,
            metadata: $this->metadata,
            maxId: $lastId,
            queryResultLimit: $this->queryResultLimit,
        );
    }

    public function getQueryComponents(): ?QueryComponents
    {
        if ($this->queryComponents !== null) {
            if ($this->queryComponents instanceof EmptyResult) {
                return null;
            }

            return $this->queryComponents;
        }

        $summarizerQuery = $this->getSummaryQuery();

        if ($summarizerQuery === null) {
            $this->queryComponents = new EmptyResult();
            return null;
        }

        return $this->queryComponents = $summarizerQuery->getQueryComponents();
    }

    private function getLowestPartitionLastId(): int|string|null
    {
        $query = new LowestPartitionLastIdQuery(
            entityManager: $this->entityManager,
            metadata: $this->metadata,
        );

        return $query->getLowestLevelPartitionMaxId();
    }

    private function getResultContext(): ResultContext
    {
        return $this->resultContext ??= ResultContextBuilder::createContext(
            query: $this->query,
            metadata: $this->metadata,
            entityManager: $this->entityManager,
            propertyAccessor: $this->propertyAccessor,
            input: $this->getQueryResult(),
            nodesLimit: $this->nodesLimit,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
        );
    }

    #[\Override]
    public function getCube(): DefaultCell
    {
        return $this->getResultContext()
            ->getCellRepository()
            ->getApexCell();
    }

    #[\Override]
    public function getTable(): DefaultTable
    {
        $dimensionality = array_values(array_filter(
            $this->query->getDimensions(),
            static fn(string $field): bool => $field !== '@values',
        ));

        return $this->table ??= DefaultTable::create(
            context: $this->getResultContext(),
            dimensionality: $dimensionality,
        );
    }

    // private function hasHierarchicalOrdering(): bool
    // {
    //     if ($this->hasHierarchicalOrdering !== null) {
    //         return $this->hasHierarchicalOrdering;
    //     }

    //     $orderBy = $this->query->getOrderBy();

    //     if ($orderBy === []) {
    //         return $this->hasHierarchicalOrdering = true;
    //     }

    //     $orderFields = array_keys($orderBy);
    //     $groupByFields = $this->query->getGroupBy();

    //     // remove @values
    //     $groupByFields = array_filter(
    //         $groupByFields,
    //         static fn(string $field): bool => $field !== '@values',
    //     );

    //     return $this->hasHierarchicalOrdering = $orderFields === $groupByFields;
    // }
}
