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

namespace Rekalogika\Analytics\Engine\SummaryVerifier;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Entity\PartitionRange;
use Rekalogika\Analytics\Engine\Handler\SummaryHandler;
use Rekalogika\Analytics\Engine\SummaryRefresher\Query\InsertIntoSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\Query\RollUpSourceToSummaryQuery;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * Compares the lowest partitions stored in a summary table with a fresh
 * roll-up of the source entities, to find the partitions that are stale
 * because a change was not detected.
 *
 * The roll-up is the same query as the one of the refresh, inserted into a
 * temporary table. Every grouping set is compared. Floating point measures are
 * compared in single precision, HyperLogLog measures by their cardinality.
 *
 * Partitions that are not expected to match are skipped: the ones with a
 * pending dirty flag, and the ones not completely summarized yet. If the
 * source has entities after the latest summarized key, the partitions from
 * the one containing the latest summarized key are not complete.
 */
final readonly class SummaryVerifier
{
    private const TEMPORARY_TABLE = 'rekalogika_summary_verify';

    private ClassMetadataWrapper $classMetadata;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SummaryMetadata $summaryMetadata,
        private SummaryHandler $summaryHandler,
    ) {
        $this->classMetadata = new ClassMetadataWrapper(
            manager: $entityManager,
            class: $summaryMetadata->getSummaryClass(),
        );
    }

    /**
     * Verifies the lowest partitions containing the source keys from start to
     * end, inclusive. If start is null, from the earliest key of the source.
     * If end is null, up to the latest summarized key.
     *
     * @param int $batchSize The number of partitions rolled up and compared
     * in one query. As with the refresh, a large batch can be slower than
     * several small ones.
     */
    public function verifyRange(
        int|string|null $start = null,
        int|string|null $end = null,
        int $batchSize = 1,
    ): VerificationResult {
        if ($batchSize < 1) {
            throw new InvalidArgumentException(\sprintf(
                'Batch size must be at least 1, got %d.',
                $batchSize,
            ));
        }

        $result = new VerificationResult();
        $start ??= $this->summaryHandler->getSource()->getEarliestKey();
        $end ??= $this->summaryHandler->getLatestKey();

        if ($start === null || $end === null) {
            return $result;
        }

        $partitionHandler = $this->summaryHandler->getPartition();
        $startPartition = $partitionHandler->createLowestPartitionFromSourceValue($start);
        $endPartition = $partitionHandler->createLowestPartitionFromSourceValue($end);

        if (PartitionUtil::isGreaterThan($startPartition, $endPartition)) {
            return $result;
        }

        $range = new PartitionRange($startPartition, $endPartition);

        $incompleteFrom = $this->getIncompleteFrom();

        foreach ($range->batch($batchSize) as $batch) {
            $this->verifyPartitions($batch, $result, $incompleteFrom);
        }

        return $result;
    }

    /**
     * Verifies randomly chosen lowest partitions stored in the summary table.
     */
    public function verifySample(int $count): VerificationResult
    {
        if ($count < 1) {
            throw new InvalidArgumentException(\sprintf(
                'Sample size must be at least 1, got %d.',
                $count,
            ));
        }

        $result = new VerificationResult();
        $incompleteFrom = $this->getIncompleteFrom();
        $partitionClass = $this->summaryMetadata->getPartition()->getPartitionClass();
        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);

        $sql = \sprintf(
            'SELECT key FROM (SELECT DISTINCT %s AS key FROM %s WHERE %s = ? AND %s < ?) k ORDER BY RANDOM() LIMIT %d',
            $this->getPartitionKeyColumn(),
            $this->classMetadata->getSQLTableName(),
            $this->getPartitionLevelColumn(),
            $this->getPartitionKeyColumn(),
            $count,
        );

        $keys = $this->getConnection()->fetchFirstColumn($sql, [
            $lowestLevel,
            $incompleteFrom,
        ]);

        /** @psalm-suppress MixedAssignment */
        foreach ($keys as $key) {
            if (!\is_int($key) && !\is_string($key)) {
                continue;
            }

            $partition = $partitionClass::createFromSourceValue($key, $lowestLevel);
            $this->verifyPartitions(new PartitionRange($partition, $partition), $result, $incompleteFrom);
        }

        return $result;
    }

    private function verifyPartitions(
        PartitionRange $range,
        VerificationResult $result,
        int $incompleteFrom,
    ): void {
        $skipped = $this->getSkippedPartitions($range, $incompleteFrom);

        // every partition skipped, e.g. the range is not summarized yet

        if (\count($skipped) === \count($range)) {
            foreach ($skipped as $partition) {
                $result->addSkipped($partition);
            }

            return;
        }

        $mismatched = $this->getConnection()->transactional(
            fn(Connection $connection): array => $this->compare($connection, $range),
        );

        foreach ($range as $partition) {
            $key = $partition->getKey();

            if (isset($skipped[$key])) {
                $result->addSkipped($partition);
            } elseif (isset($mismatched[$key])) {
                $result->addMismatched($partition);
            } else {
                $result->addVerified();
            }
        }
    }

    /**
     * The partitions in the range that are not expected to match: the ones
     * with a pending dirty flag, and the ones not completely summarized.
     *
     * @return array<int,Partition>
     */
    private function getSkippedPartitions(PartitionRange $range, int $incompleteFrom): array
    {
        $skipped = [];

        foreach ($range as $partition) {
            if ($partition->getUpperBound() > $incompleteFrom) {
                $skipped[$partition->getKey()] = $partition;
            }
        }

        $dirtyKeys = $this->getConnection()->fetchFirstColumn(
            'SELECT DISTINCT key FROM rekalogika_summary_dirty WHERE class = ? AND level = ? AND key >= ? AND key < ?',
            [
                $this->summaryMetadata->getSummaryClass(),
                $range->getLevel(),
                $range->getStart()->getLowerBound(),
                $range->getEnd()->getUpperBound(),
            ],
        );

        $partitionClass = $this->summaryMetadata->getPartition()->getPartitionClass();

        /** @psalm-suppress MixedAssignment */
        foreach ($dirtyKeys as $key) {
            if (!\is_int($key) && !\is_string($key)) {
                continue;
            }

            $partition = $partitionClass::createFromSourceValue($key, $range->getLevel());
            $skipped[$partition->getKey()] = $partition;
        }

        return $skipped;
    }

    /**
     * The lower bound of the first lowest partition that is not completely
     * summarized. If the source has entities after the latest summarized key,
     * it is the partition containing the latest summarized key.
     */
    private function getIncompleteFrom(): int
    {
        $summaryLatestKey = $this->summaryHandler->getLatestKey();

        if ($summaryLatestKey === null) {
            return PHP_INT_MIN;
        }

        $sourceLatestKey = $this->summaryHandler->getSource()->refreshLatestKey();

        if ($sourceLatestKey === null) {
            return PHP_INT_MAX;
        }

        $partitionHandler = $this->summaryHandler->getPartition();

        $summaryLatestValue = $partitionHandler->transformSourceValueToSummaryValue($summaryLatestKey);
        $sourceLatestValue = $partitionHandler->transformSourceValueToSummaryValue($sourceLatestKey);

        if ($sourceLatestValue <= $summaryLatestValue) {
            return PHP_INT_MAX;
        }

        return $partitionHandler
            ->createLowestPartitionFromSourceValue($summaryLatestKey)
            ->getLowerBound();
    }

    /**
     * Rolls up the source into a temporary table, and returns the keys of the
     * partitions whose rows differ from the stored ones.
     *
     * @return array<int,true>
     */
    private function compare(Connection $connection, PartitionRange $range): array
    {
        $table = $this->classMetadata->getSQLTableName();
        $temporaryTable = self::TEMPORARY_TABLE;

        $connection->executeStatement(\sprintf(
            'CREATE TEMPORARY TABLE %s (LIKE %s) ON COMMIT DROP',
            $temporaryTable,
            $table,
        ));

        $insertSql = (new InsertIntoSummaryQuery(
            doctrineClassMetadata: $this->classMetadata,
            summaryMetadata: $this->summaryMetadata,
            tableName: $temporaryTable,
        ))->getSQL();

        $rollUp = (new RollUpSourceToSummaryQuery(
            entityManager: $this->entityManager,
            partitionManager: $this->summaryHandler->getPartition(),
            summaryMetadata: $this->summaryMetadata,
            insertSql: $insertSql,
        ))->withBoundary($range->getStart(), $range->getEnd());

        foreach ($rollUp->getQueries() as $query) {
            $query->execute($connection);
        }

        $sql = \sprintf(
            'SELECT DISTINCT d.key FROM (
                (SELECT %1$s FROM %2$s WHERE %4$s EXCEPT ALL SELECT %1$s FROM %3$s WHERE %4$s)
                UNION ALL
                (SELECT %1$s FROM %3$s WHERE %4$s EXCEPT ALL SELECT %1$s FROM %2$s WHERE %4$s)
            ) d',
            implode(', ', $this->getComparedColumns()),
            $temporaryTable,
            $table,
            \sprintf(
                '%s = %d AND %s >= %d AND %s < %d',
                $this->getPartitionLevelColumn(),
                $range->getLevel(),
                $this->getPartitionKeyColumn(),
                $range->getStart()->getLowerBound(),
                $this->getPartitionKeyColumn(),
                $range->getEnd()->getUpperBound(),
            ),
        );

        $keys = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($connection->fetchFirstColumn($sql) as $key) {
            if (\is_int($key) || \is_string($key)) {
                $keys[(int) $key] = true;
            }
        }

        // dropped on commit, but the transaction may be nested in another
        // one, then only a savepoint is released

        $connection->executeStatement(\sprintf('DROP TABLE %s', $temporaryTable));

        return $keys;
    }

    /**
     * The columns of the summary table, except the identifier, with the
     * partition key first, aliased `key`. Floating point measures are
     * compared in single precision, HyperLogLog measures by their
     * cardinality.
     *
     * @return list<string>
     */
    private function getComparedColumns(): array
    {
        $columns = [
            \sprintf('%s AS key', $this->getPartitionKeyColumn()),
            $this->getPartitionLevelColumn(),
        ];

        foreach ($this->summaryMetadata->getLeafDimensions() as $dimensionMetadata) {
            $columns[] = $this->classMetadata->getSQLFieldName($dimensionMetadata->getName());
        }

        foreach ($this->summaryMetadata->getMeasures() as $property => $measureMetadata) {
            if ($measureMetadata->isVirtual()) {
                continue;
            }

            $column = $this->classMetadata->getSQLFieldName($property);

            $columns[] = match ($this->classMetadata->getScalarType($property)) {
                Types::FLOAT, Types::SMALLFLOAT => \sprintf('CAST(%s AS REAL)', $column),
                'rekalogika_hll' => \sprintf('HLL_CARDINALITY(%s)', $column),
                default => $column,
            };
        }

        $columns[] = $this->classMetadata
            ->getSQLFieldName($this->summaryMetadata->getGroupingsProperty());

        return $columns;
    }

    private function getPartitionKeyColumn(): string
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();

        return $this->classMetadata->getSQLFieldName(\sprintf(
            '%s.%s',
            $partitionMetadata->getName(),
            $partitionMetadata->getPartitionKeyProperty(),
        ));
    }

    private function getPartitionLevelColumn(): string
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();

        return $this->classMetadata->getSQLFieldName(\sprintf(
            '%s.%s',
            $partitionMetadata->getName(),
            $partitionMetadata->getPartitionLevelProperty(),
        ));
    }

    private function getConnection(): Connection
    {
        return $this->entityManager->getConnection();
    }
}
