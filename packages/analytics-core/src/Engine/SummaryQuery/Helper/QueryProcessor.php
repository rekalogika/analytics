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
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Query\LowestPartitionLastIdQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Query\SummaryQuery;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @internal
 */
final class QueryProcessor
{
    private SummaryQuery|EmptyResult|null $summarizerQuery = null;

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $queryResult = null;

    public function __construct(
        private readonly DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly EntityManagerInterface $entityManager,
        private int $queryResultLimit,
    ) {}

    /**
     * @return list<array<string,mixed>>
     */
    public function getQueryResult(): array
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

    private function getLowestPartitionLastId(): int|string|null
    {
        $query = new LowestPartitionLastIdQuery(
            entityManager: $this->entityManager,
            metadata: $this->metadata,
        );

        return $query->getLowestLevelPartitionMaxId();
    }
}
