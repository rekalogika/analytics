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

namespace Rekalogika\Analytics\Engine\SummaryManager\Handler;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Entity\DirtyPartition;
use Rekalogika\Analytics\Engine\Entity\DirtyPartitionCollection;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStrategy;
use Rekalogika\Analytics\Engine\SummaryManager\PartitionRange;
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

    public function createDirtyFlagForPartition(Partition $partition): DirtyFlag
    {
        return new DirtyFlag(
            class: $this->metadata->getSummaryClass(),
            level: $partition->getLevel(),
            key: $partition->getKey(),
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
            ->andWhere('df.level IS NOT NULL')
            ->andWhere('df.key IS NOT NULL')
            ->setParameter('class', $summaryClass)

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

            if (($maximumAge = $refreshAgentStrategy->getMaximumAge()) === null) {
                $expression = $queryBuilder->expr()->orX(
                    $expression,
                    $queryBuilder->expr()->gte(
                        'DATE_SUB(MAX(df.created), :range, \'second\')',
                        'MIN(df.created)',
                    ),
                );

                $queryBuilder->setParameter('range', $maximumAge);
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
    }
}
