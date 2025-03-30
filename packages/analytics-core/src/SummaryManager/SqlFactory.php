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

namespace Rekalogika\Analytics\SummaryManager;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Summary\Partition;
use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManager;
use Rekalogika\Analytics\SummaryManager\Query\DeleteExistingSummaryQuery;
use Rekalogika\Analytics\SummaryManager\Query\InsertIntoSummaryQuery;
use Rekalogika\Analytics\SummaryManager\Query\RollUpSourceToSummaryPerSourceQuery;
use Rekalogika\Analytics\SummaryManager\Query\RollUpSummaryToSummaryCubingStrategyQuery;
use Rekalogika\Analytics\SummaryManager\Query\RollUpSummaryToSummaryGroupAllStrategyQuery;

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
        private readonly PartitionManager $partitionManager,
        ?string $summaryToSummaryRollUpClass = null,
    ) {
        $this->summaryToSummaryRollUpClass = $summaryToSummaryRollUpClass
            ?? RollUpSummaryToSummaryGroupAllStrategyQuery::class;

        $classMetadata = $entityManager
            ->getClassMetadata($this->summaryMetadata->getSummaryClass());

        $this->doctrineClassMetadata = new ClassMetadataWrapper($classMetadata);
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

    /**
     * @param class-string $sourceClass
     * @return iterable<string>
     */
    private function createSelectForRollingUpSingleSourceToSummaryQuery(
        string $sourceClass,
        Partition $start,
        Partition $end,
    ): iterable {
        $query = new RollUpSourceToSummaryPerSourceQuery(
            sourceClass: $sourceClass,
            queryBuilder: $this->entityManager->createQueryBuilder(),
            partitionManager: $this->partitionManager,
            metadata: $this->summaryMetadata,
            start: $start,
            end: $end,
        );

        yield from $query->getSQL();
    }

    /**
     * @param class-string $sourceClass
     * @return iterable<string>
     */
    private function createInsertIntoSelectForRollingUpSingleSourceToSummaryQuery(
        string $sourceClass,
        Partition $start,
        Partition $end,
    ): iterable {
        $selects = $this->createSelectForRollingUpSingleSourceToSummaryQuery(
            sourceClass: $sourceClass,
            start: $start,
            end: $end,
        );

        $insertInto = $this->createInsertIntoSummaryQuery();

        foreach ($selects as $select) {
            yield $insertInto . ' ' . $select;
        }
    }

    /**
     * @return iterable<string>
     */
    public function createInsertIntoSelectForRollingUpSourceToSummaryQuery(
        Partition $start,
        Partition $end,
    ): iterable {
        $sources = $this->summaryMetadata->getSourceClasses();

        foreach ($sources as $source) {
            $insertIntoSelects = $this
                ->createInsertIntoSelectForRollingUpSingleSourceToSummaryQuery(
                    sourceClass: $source,
                    start: $start,
                    end: $end,
                );

            yield from $insertIntoSelects;
        }
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
            queryBuilder: $this->entityManager->createQueryBuilder(),
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
