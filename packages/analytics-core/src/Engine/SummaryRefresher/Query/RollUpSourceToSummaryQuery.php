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

namespace Rekalogika\Analytics\Engine\SummaryRefresher\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Handler\PartitionHandler;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherQuery;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;

final class RollUpSourceToSummaryQuery implements SummaryRefresherQuery
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly PartitionHandler $partitionManager,
        private readonly SummaryMetadata $summaryMetadata,
        private readonly string $insertSql,
        private readonly ?Partition $start = null,
        private readonly ?Partition $end = null,
    ) {}

    #[\Override]
    public function withBoundary(Partition $start, Partition $end): static
    {
        return new self(
            entityManager: $this->entityManager,
            partitionManager: $this->partitionManager,
            summaryMetadata: $this->summaryMetadata,
            insertSql: $this->insertSql,
            start: $start,
            end: $end,
        );
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    #[\Override]
    public function getQueries(): iterable
    {
        // constants
        // @todo make this configurable

        $maximumGroupingSets = 4000;
        $maximumChunkSize = 4000;

        // get the flattened grouping sets

        $groupBy = $this->summaryMetadata->getGroupByExpression();

        $flattened = $groupBy->flatten();
        $groupingSet = iterator_to_array($flattened, false)[0]
            ?? throw new UnexpectedValueException('Expected at least one grouping set after flattening.');

        if (!$groupingSet instanceof GroupingSet) {
            throw new UnexpectedValueException(\sprintf(
                'Expected instance of %s, got %s',
                GroupingSet::class,
                \get_class($groupingSet),
            ));
        }

        // if the count of grouping sets is below the maximum threshold, we pass
        // the group by as is

        if ($groupingSet->count() < $maximumGroupingSets) {
            $worker = new RollUpSourceToSummaryQueryWorker(
                entityManager: $this->entityManager,
                partitionManager: $this->partitionManager,
                summaryMetadata: $this->summaryMetadata,
                insertSql: $this->insertSql,
                groupBy: $groupBy,
            );

            if ($this->start !== null && $this->end !== null) {
                $worker = $worker->withBoundary($this->start, $this->end);
            }

            yield from $worker->getQueries();
            return;
        }

        // otherwise, we need to chunk the grouping sets into smaller batches

        $count = $groupingSet->count();
        $numberOfBatch = (int) ceil($count / $maximumChunkSize);
        $numberPerBatch = (int) ceil($count / $numberOfBatch);

        if ($numberPerBatch < 1) {
            $numberPerBatch = 1;
        }

        $fieldSets = iterator_to_array($groupingSet, false);
        $batches = array_chunk($fieldSets, $numberPerBatch);

        foreach ($batches as $currentBatch) {
            $currentGroupingSet = new GroupingSet(...$currentBatch);
            $currentGroupBy = new GroupBy($currentGroupingSet);

            $worker = new RollUpSourceToSummaryQueryWorker(
                entityManager: $this->entityManager,
                partitionManager: $this->partitionManager,
                summaryMetadata: $this->summaryMetadata,
                insertSql: $this->insertSql,
                groupBy: $currentGroupBy,
            );

            if ($this->start !== null && $this->end !== null) {
                $worker = $worker->withBoundary($this->start, $this->end);
            }

            foreach ($worker->getQueries() as $query) {
                yield $query;
            }
        }
    }
}
