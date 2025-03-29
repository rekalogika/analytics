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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Result;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\SummaryManager\Query\SummarizerQuery;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\BalancedNormalTableToBalancedTableTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\NormalTableToTreeTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\QueryResultToTableTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\TableToNormalTableTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\TreeToBalancedNormalTableTransformer;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

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

    /**
     * @param class-string $summaryClass
     * @param SummarizerQuery $summarizerQuery
     */
    public function __construct(
        private string $summaryClass,
        private SummaryQuery $query,
        private SummaryMetadata $metadata,
        private SummarizerQuery $summarizerQuery,
        private PropertyAccessorInterface $propertyAccessor,
        private EntityManagerInterface $entityManager,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function getQueryResult(): array
    {
        return $this->queryResult ??= $this->summarizerQuery->getQueryResult();
    }

    public function getUnbalancedTable(): DefaultTable
    {
        return $this->unbalancedTable ??= QueryResultToTableTransformer::transform(
            query: $this->query,
            metadata: $this->metadata,
            entityManager: $this->entityManager,
            propertyAccessor: $this->propertyAccessor,
            input: $this->getQueryResult(),
        );
    }

    public function getUnbalancedNormalTable(): DefaultNormalTable
    {
        return $this->unbalancedNormalTable ??= TableToNormalTableTransformer::transform(
            summaryQuery: $this->query,
            metadata: $this->metadata,
            input: $this->getUnbalancedTable(),
        );
    }

    #[\Override]
    public function getTree(): DefaultTree
    {
        return $this->tree ??= NormalTableToTreeTransformer::transform(
            normalTable: $this->getUnbalancedNormalTable(),
            query: $this->query,
            metadata: $this->metadata,
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
        return $this->table ??= BalancedNormalTableToBalancedTableTransformer::transform(normalTable: $this->getNormalTable());
    }
}
