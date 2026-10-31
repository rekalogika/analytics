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

namespace Rekalogika\Analytics\Engine\Handler;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Entity\DirtyPartition;
use Rekalogika\Analytics\Engine\Entity\DirtyPartitionCollection;
use Rekalogika\Analytics\Engine\Entity\PartitionRange;
use Rekalogika\Analytics\Engine\Entity\RefreshLog;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStrategy;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Engine\Util\RefreshAgentStrategyUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * Manages the partitions of a specific summary class.
 */
final readonly class DirtyFlagsHandler
{
    public function __construct(
        private SummaryMetadata $metadata,
        private EntityManagerInterface $entityManager,
        private PartitionHandler $partitionHandler,
    ) {}


    public function createDirtyFlagForSourceEntity(object $entity): DirtyFlag
    {
        $partition = $this->partitionHandler->getLowestPartitionFromEntity($entity);

        return $this->createDirtyFlagForPartition($partition);
    }

    public function createDirtyFlagForPartition(
        Partition $partition,
        ?\DateTimeImmutable $created = null,
    ): DirtyFlag {
        return new DirtyFlag(
            class: $this->metadata->getSummaryClass(),
            level: $partition->getLevel(),
            key: $partition->getKey(),
            created: $created,
        );
    }

    public function getDirtyPartitions(
        RefreshAgentStrategy $refreshAgentStrategy,
        int $batchSize = 1000,
    ): DirtyPartitionCollection {
        $summaryClass = $this->metadata->getSummaryClass();
        $partitionClass = $this->metadata->getPartition()->getPartitionClass();

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select(\sprintf(
                'NEW %s(
                    df.class,
                    df.level,
                    df.key,
                    MIN(df.created),
                    MAX(df.created),
                    COUNT(df.id),
                    \'%s\'
                )',
                DirtyPartition::class,
                $partitionClass,
            ))
            ->addSelect('MIN(df.created) AS HIDDEN earliest')
            ->addSelect('MAX(df.created) AS HIDDEN latest')
            ->from(DirtyFlag::class, 'df')

            ->where('df.class = :class')
            ->andWhere('df.level = :level')
            ->andWhere('df.key IS NOT NULL')
            ->setParameter('class', $summaryClass)
            ->setParameter('level', $this->getLowestLevel())

            ->groupBy('df.class, df.level, df.key')

            ->orderBy('earliest', 'ASC')

            ->setMaxResults($batchSize);

        if (($minimumAge = $refreshAgentStrategy->getMinimumAge()) !== null) {
            $minimumAgeThreshold = (new \DateTimeImmutable())
                ->modify(\sprintf('-%d seconds', $minimumAge));

            $queryBuilder
                ->andHaving('MIN(df.created) <= :minimumAgeThreshold')
                ->setParameter(
                    'minimumAgeThreshold',
                    $minimumAgeThreshold,
                    Types::DATETIME_IMMUTABLE,
                );
        }

        if (($minimumIdleDelay = $refreshAgentStrategy->getMinimumIdleDelay()) !== null) {
            $minimumIdleDelayThreshold = (new \DateTimeImmutable())
                ->modify(\sprintf('-%d seconds', $minimumIdleDelay));

            $expression = $queryBuilder->expr()->lte(
                'MAX(df.created)',
                ':minimumIdleDelayThreshold',
            );

            $queryBuilder->setParameter(
                'minimumIdleDelayThreshold',
                $minimumIdleDelayThreshold,
                Types::DATETIME_IMMUTABLE,
            );

            if (($maximumAge = $refreshAgentStrategy->getMaximumAge()) !== null) {
                $maximumAgeThreshold = (new \DateTimeImmutable())
                    ->modify(\sprintf('-%d seconds', $maximumAge));

                $expression = $queryBuilder->expr()->orX(
                    $expression,
                    $queryBuilder->expr()->lte(
                        'MIN(df.created)',
                        ':maximumAgeThreshold',
                    ),
                );

                $queryBuilder->setParameter(
                    'maximumAgeThreshold',
                    $maximumAgeThreshold,
                    Types::DATETIME_IMMUTABLE,
                );
            }

            $queryBuilder->andHaving($expression);
        }

        $query = $queryBuilder->getQuery();

        /**
         * @var list<DirtyPartition> $result
         */
        $result = $query->getResult();

        return new DirtyPartitionCollection(
            summaryClass: $summaryClass,
            dirtyPartitions: $result,
        );
    }

    /**
     * Gets the dirty partitions of the specified upper level, excluding the
     * partitions that have been refreshed within the cooldown period.
     *
     * @return list<Partition>
     */
    public function getDirtyUpperPartitions(
        int $level,
        ?int $cooldown,
        \DateTimeImmutable $now,
    ): array {
        /** @var list<array{key:int|string}> */
        $result = $this->entityManager->createQueryBuilder()
            ->select('df.key')
            ->from(DirtyFlag::class, 'df')
            ->where('df.class = :class')
            ->andWhere('df.level = :level')
            ->andWhere('df.key IS NOT NULL')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->setParameter('level', $level)
            ->groupBy('df.key')
            ->orderBy('df.key', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $lastRefreshes = $this->getLastRefreshes($cooldown, $now);
        $partitionClass = $this->metadata->getPartition()->getPartitionClass();
        $partitions = [];

        foreach ($result as $row) {
            $key = (int) $row['key'];

            if (isset($lastRefreshes[$level][$key])) {
                continue;
            }

            $partitions[] = $partitionClass::createFromSourceValue(
                source: $key,
                level: $level,
            );
        }

        return $partitions;
    }

    /**
     * Gets the earliest time one of the remaining dirty partitions will be
     * ready to be refreshed, assuming no more changes are made to the source.
     * Returns null if there are no dirty flags.
     */
    public function getNextRefreshTime(
        RefreshAgentStrategy $refreshAgentStrategy,
        \DateTimeImmutable $now,
    ): ?\DateTimeImmutable {
        /** @var list<array{level:int|string|null,key:int|string|null,earliest:string,latest:string}> */
        $result = $this->entityManager->createQueryBuilder()
            ->select('df.level, df.key')
            ->addSelect('MIN(df.created) AS earliest')
            ->addSelect('MAX(df.created) AS latest')
            ->from(DirtyFlag::class, 'df')
            ->where('df.class = :class')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->groupBy('df.level, df.key')
            ->getQuery()
            ->getArrayResult();

        if ($result === []) {
            return null;
        }

        $lowestLevel = $this->getLowestLevel();
        $levels = $this->metadata->getPartition()->getPartitionClass()::getAllLevels();
        $cooldown = $refreshAgentStrategy->getUpperPartitionCooldown();
        $lastRefreshes = $this->getLastRefreshes($cooldown, $now);
        $nextRefreshTime = null;

        foreach ($result as $row) {
            $level = $row['level'] === null ? null : (int) $row['level'];
            $key = $row['key'] === null ? null : (int) $row['key'];

            // flags of levels no longer used by the partition will never be
            // processed

            if ($level !== null && !\in_array($level, $levels, true)) {
                continue;
            }

            if ($level === null || $key === null || $level === $lowestLevel) {
                // new records & the lowest level partitions are subject to the
                // refresh agent strategy

                $refreshTime = RefreshAgentStrategyUtil::getRefreshTime(
                    refreshAgentStrategy: $refreshAgentStrategy,
                    earliest: new \DateTimeImmutable($row['earliest']),
                    latest: new \DateTimeImmutable($row['latest']),
                );
            } elseif ($cooldown !== null && isset($lastRefreshes[$level][$key])) {
                // upper level partitions in the cooldown period are refreshed
                // after the cooldown period ends

                $refreshTime = $lastRefreshes[$level][$key]
                    ->modify(\sprintf('+%d seconds', $cooldown));
            } else {
                $refreshTime = $now;
            }

            if ($nextRefreshTime === null || $refreshTime < $nextRefreshTime) {
                $nextRefreshTime = $refreshTime;
            }
        }

        return $nextRefreshTime;
    }

    /**
     * Gets the time range of the dirty flags indicating new records. Returns
     * null if there are no such flags.
     *
     * @return array{\DateTimeImmutable,\DateTimeImmutable}|null
     */
    public function getNewFlagsTimeRange(): ?array
    {
        /** @var array{earliest:string|null,latest:string|null} */
        $result = $this->entityManager->createQueryBuilder()
            ->select('MIN(df.created) AS earliest')
            ->addSelect('MAX(df.created) AS latest')
            ->from(DirtyFlag::class, 'df')
            ->where('df.class = :class')
            ->andWhere('df.level IS NULL')
            ->andWhere('df.key IS NULL')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->getQuery()
            ->getSingleResult();

        if ($result['earliest'] === null || $result['latest'] === null) {
            return null;
        }

        return [
            new \DateTimeImmutable($result['earliest']),
            new \DateTimeImmutable($result['latest']),
        ];
    }

    /**
     * Gets the last refresh time of the upper level partitions refreshed
     * within the cooldown period.
     *
     * @return array<int,array<int,\DateTimeImmutable>> level => key => time
     */
    private function getLastRefreshes(
        ?int $cooldown,
        \DateTimeImmutable $now,
    ): array {
        if ($cooldown === null) {
            return [];
        }

        /** @var list<array{level:int|string,key:int|string,refreshed:string}> */
        $result = $this->entityManager->createQueryBuilder()
            ->select('rl.level, rl.key')
            ->addSelect('MAX(rl.refreshed) AS refreshed')
            ->from(RefreshLog::class, 'rl')
            ->where('rl.class = :class')
            ->andWhere('rl.refreshed > :threshold')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->setParameter(
                'threshold',
                $now->modify(\sprintf('-%d seconds', $cooldown)),
                Types::DATETIME_IMMUTABLE,
            )
            ->groupBy('rl.level, rl.key')
            ->getQuery()
            ->getArrayResult();

        $lastRefreshes = [];

        foreach ($result as $row) {
            $lastRefreshes[(int) $row['level']][(int) $row['key']] =
                new \DateTimeImmutable($row['refreshed']);
        }

        return $lastRefreshes;
    }

    public function createRefreshLog(
        Partition $partition,
        \DateTimeImmutable $refreshed,
    ): RefreshLog {
        return new RefreshLog(
            class: $this->metadata->getSummaryClass(),
            level: $partition->getLevel(),
            key: $partition->getKey(),
            refreshed: $refreshed,
        );
    }

    /**
     * Removes the refresh logs older than the specified time.
     */
    public function removeRefreshLogs(\DateTimeImmutable $before): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(RefreshLog::class, 'rl')
            ->where('rl.class = :class')
            ->andWhere('rl.refreshed <= :before')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->setParameter('before', $before, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();
    }

    private function getLowestLevel(): int
    {
        return PartitionUtil::getLowestLevel(
            $this->metadata->getPartition()->getPartitionClass(),
        );
    }

    public function removeDirtyFlags(PartitionRange $range): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(DirtyFlag::class, 's')
            ->where('s.class = :class')
            ->andWhere('s.level = :level')
            ->andWhere('s.key >= :start')
            ->andWhere('s.key < :end')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->setParameter('level', $range->getLevel())
            ->setParameter('start', $range->getLowerBound())
            ->setParameter('end', $range->getUpperBound())
            ->getQuery()
            ->execute();
    }

    public function removeNewFlags(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(DirtyFlag::class, 's')
            ->where('s.class = :class')
            ->andWhere('s.level IS NULL')
            ->andWhere('s.key IS NULL')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->getQuery()
            ->execute();
    }

    public function removeAllDirtyFlags(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(DirtyFlag::class, 's')
            ->where('s.class = :class')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(RefreshLog::class, 's')
            ->where('s.class = :class')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->getQuery()
            ->execute();
    }
}
