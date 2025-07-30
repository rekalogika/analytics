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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Exception\HierarchicalOrderingRequired;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\LowestPartitionLastIdQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SummaryQuery;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\BalancedNormalTableToBalancedTableTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\MetadataOrderByResolver;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\EmptyResult;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\RowCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\TreeNodeFactory;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\NormalTableToTreeTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\QueryResultToTableTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\TableToNormalTableTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\TreeToBalancedNormalTableTransformer;
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

    private ?DefaultTable $unbalancedTable = null;

    private ?DefaultNormalTable $unbalancedNormalTable = null;

    private ?DefaultTree $tree = null;

    private ?DefaultNormalTable $normalTable = null;

    private ?DefaultTable $table = null;

    private ?bool $hasHierarchicalOrdering = null;

    private readonly TreeNodeFactory $treeNodeFactory;

    private readonly RowCollection $rowCollection;
    // private readonly DimensionCollection $dimensionCollection;
    private readonly DimensionFactory $dimensionFactory;

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
        int $fillingNodesLimit,
        private int $queryResultLimit,
    ) {
        $this->rowCollection = new RowCollection();

        $this->dimensionFactory = new DimensionFactory(
            new MetadataOrderByResolver(
                metadata: $this->metadata,
                query: $this->query,
            ),
        );

        // $this->dimensionCollection = $this->dimensionFactory->getDimensionCollection();

        $this->treeNodeFactory = new TreeNodeFactory(
            fillingNodesLimit: $fillingNodesLimit,
            rowCollection: $this->rowCollection,
        );
    }

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

        // if no measure is selected, don't bother running the query
        if ($this->query->getSelect() === []) {
            $this->queryComponents = new EmptyResult();
            return null;
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

    private function getUnbalancedTable(): DefaultTable
    {
        return $this->unbalancedTable ??= QueryResultToTableTransformer::transform(
            query: $this->query,
            metadata: $this->metadata,
            entityManager: $this->entityManager,
            propertyAccessor: $this->propertyAccessor,
            rowCollection: $this->rowCollection,
            dimensionFactory: $this->dimensionFactory,
            input: $this->getQueryResult(),
        );
    }

    private function getUnbalancedNormalTable(): DefaultNormalTable
    {
        if (!$this->hasHierarchicalOrdering()) {
            throw new HierarchicalOrderingRequired();
        }

        return $this->unbalancedNormalTable ??= TableToNormalTableTransformer::transform(
            query: $this->query,
            input: $this->getUnbalancedTable(),
            rowCollection: $this->rowCollection,
            dimensionFactory: $this->dimensionFactory,
            metadata: $this->metadata,
        );
    }

    #[\Override]
    public function getTree(): DefaultTree
    {
        return $this->tree ??= NormalTableToTreeTransformer::transform(
            label: $this->label,
            normalTable: $this->getUnbalancedNormalTable(),
            treeNodeFactory: $this->treeNodeFactory,
            rowCollection: $this->rowCollection,
        );
    }

    #[\Override]
    public function getNormalTable(): DefaultNormalTable
    {
        return $this->normalTable ??= TreeToBalancedNormalTableTransformer::transform(tree: $this->getTree());
    }

    #[\Override]
    public function getTable(): DefaultTable
    {
        if (!$this->hasHierarchicalOrdering()) {
            return $this->getUnbalancedTable();
        }

        return $this->table ??= BalancedNormalTableToBalancedTableTransformer::transform(normalTable: $this->getNormalTable());
    }

    private function hasHierarchicalOrdering(): bool
    {
        if ($this->hasHierarchicalOrdering !== null) {
            return $this->hasHierarchicalOrdering;
        }

        $orderBy = $this->query->getOrderBy();

        if ($orderBy === []) {
            return $this->hasHierarchicalOrdering = true;
        }

        $orderFields = array_keys($orderBy);
        $groupByFields = $this->query->getGroupBy();

        return $this->hasHierarchicalOrdering = $orderFields === $groupByFields;
    }
}
