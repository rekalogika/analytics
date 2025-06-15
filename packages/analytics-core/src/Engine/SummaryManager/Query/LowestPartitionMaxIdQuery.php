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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Core\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

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

        $partitionKeyProperty = $this->metadata->getPartition()
            ->getPartitionKeyProperty();

        $partitionLevelProperty = $this->metadata->getPartition()
            ->getPartitionLevelProperty();

        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->from($this->metadata->getSummaryClass(), 'root')
            ->select(\sprintf(
                'MAX(root.%s.%s)',
                $partitionSummaryProperty,
                $partitionKeyProperty,
            ))
            ->andWhere(\sprintf(
                'root.%s.%s = :partitionLevel',
                $partitionSummaryProperty,
                $partitionLevelProperty,
            ))
            ->setParameter('partitionLevel', $lowestLevel)
        ;

        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        if ($result === null || \is_int($result) || \is_string($result)) {
            return $result;
        }

        throw new UnexpectedValueException(\sprintf(
            'The result of the query is not an integer or string, got "%s"',
            \gettype($result),
        ));
    }
}
