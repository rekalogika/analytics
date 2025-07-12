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
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Entity\DirtyPartition;
use Rekalogika\Analytics\Engine\Entity\DirtyPartitionCollection;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * Manages the partitions of a specific summary class.
 */
final readonly class DirtyFlagsHandler
{
    public function __construct(
        private SummaryMetadata $metadata,
        private EntityManagerInterface $entityManager,
    ) {}

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
}
