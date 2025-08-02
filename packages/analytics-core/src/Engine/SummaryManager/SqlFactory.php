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
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\PartitionHandler;
use Rekalogika\Analytics\Engine\SummaryManager\Query\DeleteExistingSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\InsertIntoSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\RollUpSourceToSummaryPerSourceQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\RollUpSummaryToSummaryCubingStrategyQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\RollUpSummaryToSummaryGroupAllStrategyQuery;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;

final class SqlFactory
{
    /**
     * @param RollUpSummaryToSummaryCubingStrategyQuery::class|RollUpSummaryToSummaryGroupAllStrategyQuery::class $summaryToSummaryRollUpClass
     */
    private string $summaryToSummaryRollUpClass = RollUpSummaryToSummaryCubingStrategyQuery::class;

    private readonly ClassMetadataWrapper $doctrineClassMetadata;

    private ?string $insertInto = null;

    /**
     * @param class-string<RollUpSummaryToSummaryCubingStrategyQuery>|class-string<RollUpSummaryToSummaryGroupAllStrategyQuery> $summaryToSummaryRollUpClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $summaryMetadata,
        private readonly PartitionHandler $partitionManager,
        ?string $summaryToSummaryRollUpClass = null,
    ) {
        $this->summaryToSummaryRollUpClass = $summaryToSummaryRollUpClass
            ?? RollUpSummaryToSummaryGroupAllStrategyQuery::class;

        $this->doctrineClassMetadata = new ClassMetadataWrapper(
            manager: $this->entityManager,
            class: $this->summaryMetadata->getSummaryClass(),
        );
    }

    /**
     * @return iterable<string>
     */
    public function createDeleteSummaryQuery(
        Partition $start,
        Partition $end,
    ): iterable {
        $query = new DeleteExistingSummaryQuery(
            entityManager: $this->entityManager,
            summaryMetadata: $this->summaryMetadata,
            start: $start,
            end: $end,
        );

        yield from $query->getSQL();
    }

    private function createInsertIntoSummaryQuery(): string
    {
        if ($this->insertInto !== null) {
            return $this->insertInto;
        }

        $query = new InsertIntoSummaryQuery(
            doctrineClassMetadata: $this->doctrineClassMetadata,
            summaryMetadata: $this->summaryMetadata,
        );

        return $this->insertInto = $query->getSQL();
    }

    private ?RollUpSourceToSummaryPerSourceQuery $rollUpSourceToSummaryQuery = null;

    public function getRollUpSourceToSummaryQuery(): RollUpSourceToSummaryPerSourceQuery
    {
        return $this->rollUpSourceToSummaryQuery ??=
            new RollUpSourceToSummaryPerSourceQuery(
                entityManager: $this->entityManager,
                partitionManager: $this->partitionManager,
                summaryMetadata: $this->summaryMetadata,
            );
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    private function createSelectForRollingUpSingleSourceToSummaryQuery(
        Partition $start,
        Partition $end,
    ): iterable {
        return $this
            ->getRollUpSourceToSummaryQuery()
            ->withBoundary($start, $end)
            ->getQueries();
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    private function createInsertIntoSelectForRollingUpSingleSourceToSummaryQuery(
        Partition $start,
        Partition $end,
    ): iterable {
        $selects = $this->createSelectForRollingUpSingleSourceToSummaryQuery(
            start: $start,
            end: $end,
        );

        $insertInto = $this->createInsertIntoSummaryQuery();

        foreach ($selects as $select) {
            yield $select->prependSql($insertInto);
        }
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    public function createInsertIntoSelectForRollingUpSourceToSummaryQuery(
        Partition $start,
        Partition $end,
    ): iterable {
        $source = $this->summaryMetadata->getSourceClass();

        $insertIntoSelects = $this
            ->createInsertIntoSelectForRollingUpSingleSourceToSummaryQuery(
                start: $start,
                end: $end,
            );

        yield from $insertIntoSelects;
    }

    /**
     * @return iterable<string>
     */
    private function createSelectForRollingUpSummaryToSummaryQuery(
        Partition $start,
        Partition $end,
    ): iterable {
        $class = $this->summaryToSummaryRollUpClass;

        if (
            !is_a($class, RollUpSummaryToSummaryCubingStrategyQuery::class, true)
            && !is_a($class, RollUpSummaryToSummaryGroupAllStrategyQuery::class, true)
        ) {
            throw new LogicException(\sprintf(
                'Class "%s" must be an instance of "%s" or "%s"',
                $class,
                RollUpSummaryToSummaryCubingStrategyQuery::class,
                RollUpSummaryToSummaryGroupAllStrategyQuery::class,
            ));
        }

        $query = new $class(
            entityManager: $this->entityManager,
            metadata: $this->summaryMetadata,
            start: $start,
            end: $end,
        );

        // @phpstan-ignore method.notFound
        yield from $query->getSQL();
    }

    /**
     * @return iterable<string>
     */
    public function createInsertIntoSelectForRollingUpSummaryToSummaryQuery(
        Partition $start,
        Partition $end,
    ): iterable {
        $selects = $this->createSelectForRollingUpSummaryToSummaryQuery(
            start: $start,
            end: $end,
        );

        $insertInto = $this->createInsertIntoSummaryQuery();

        foreach ($selects as $select) {
            yield $insertInto . ' ' . $select;
        }
    }
}
