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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Entity\DirtyPartition;
use Rekalogika\Analytics\Engine\Entity\DirtyPartitionCollection;
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

    public function getDirtyPartitions(int $limit = 100): DirtyPartitionCollection
    {
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
            ->from(DirtyFlag::class, 'df')
            ->where('df.class = :class')
            ->setParameter('class', $summaryClass)
            ->andWhere('df.level IS NOT NULL')
            ->andWhere('df.key IS NOT NULL')
            ->groupBy('df.class, df.level, df.key')
            ->orderBy('earliest', 'ASC')
            ->setMaxResults($limit);

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
}
