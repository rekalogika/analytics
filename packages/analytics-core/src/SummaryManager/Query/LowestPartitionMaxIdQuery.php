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

namespace Rekalogika\Analytics\SummaryManager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Util\PartitionUtil;

final readonly class LowestPartitionMaxIdQuery
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SummaryMetadata $metadata,
    ) {}

    public function getLowestLevelPartitionMaxId(): int|string|null
    {
        $partitionSummaryProperty = $this->metadata->getPartition()
            ->getSummaryProperty();

        $partitionClass = $this->metadata->getPartition()
            ->getPartitionClass();

        $partitionIdProperty = $this->metadata->getPartition()
            ->getPartitionIdProperty();

        $partitionLevelProperty = $this->metadata->getPartition()
            ->getPartitionLevelProperty();

        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->from($this->metadata->getSummaryClass(), 's')
            ->select(\sprintf(
                'MAX(s.%s.%s)',
                $partitionSummaryProperty,
                $partitionIdProperty,
            ))
            // ->where(sprintf(
            //     's.%s.%s = :partition',
            //     $partitionSummaryProperty,
            //     $partitionIdProperty
            // ))
            // ->setParameter('partition', $this->metadata->getPartition())
            ->andWhere(\sprintf(
                's.%s.%s = :partitionLevel',
                $partitionSummaryProperty,
                $partitionLevelProperty,
            ))
            ->setParameter('partitionLevel', $lowestLevel)
        ;

        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        if ($result === null || \is_int($result) || \is_string($result)) {
            return $result;
        }


        throw new \RuntimeException('The result of the query is not an integer or string.');
    }
}
