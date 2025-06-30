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
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Exception\HierarchicalOrderingRequired;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SummarizerQuery;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\BalancedNormalTableToBalancedTableTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\NormalTableToTreeTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\QueryResultToTableTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\TableToNormalTableTransformer;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\TreeToBalancedNormalTableTransformer;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @internal
 */
final class DefaultResult implements Result
{
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

    private readonly DefaultTreeNodeFactory $treeNodeFactory;

    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private readonly TranslatableInterface $label,
        private readonly string $summaryClass,
        private readonly DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly SummarizerQuery $summarizerQuery,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly EntityManagerInterface $entityManager,
        int $fillingNodesLimit,
    ) {
        $this->treeNodeFactory = new DefaultTreeNodeFactory(
            fillingNodesLimit: $fillingNodesLimit,
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
        return $this->queryResult ??= $this->summarizerQuery->getQueryResult();
    }

    private function getUnbalancedTable(): DefaultTable
    {
        return $this->unbalancedTable ??= QueryResultToTableTransformer::transform(
            query: $this->query,
            metadata: $this->metadata,
            entityManager: $this->entityManager,
            propertyAccessor: $this->propertyAccessor,
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
            metadata: $this->metadata,
            input: $this->getUnbalancedTable(),
        );
    }

    #[\Override]
    public function getTree(): DefaultTree
    {
        return $this->tree ??= NormalTableToTreeTransformer::transform(
            label: $this->label,
            normalTable: $this->getUnbalancedNormalTable(),
            treeNodeFactory: $this->treeNodeFactory,
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
