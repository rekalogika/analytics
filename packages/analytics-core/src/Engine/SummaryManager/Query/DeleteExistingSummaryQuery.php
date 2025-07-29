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
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class DeleteExistingSummaryQuery
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SummaryMetadata $summaryMetadata,
        private Partition $start,
        private Partition $end,
    ) {}

    /**
     * @return iterable<string>
     */
    public function getSQL(): iterable
    {
        $summaryClassName = $this->summaryMetadata->getSummaryClass();
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $partitionProperty = $partitionMetadata->getName();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

        $level = $this->start->getLevel();

        if ($level !== $this->end->getLevel()) {
            throw new InvalidArgumentException(\sprintf(
                'The start and end partitions must be on the same level, but got "%d" and "%d"',
                $this->start->getLevel(),
                $this->end->getLevel(),
            ));
        }

        $start = $this->start->getLowerBound();
        $end = $this->end->getUpperBound();

        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->delete($summaryClassName, 'root');

        $queryBuilder
            ->andWhere(\sprintf(
                'root.%s.%s >= %s',
                $partitionProperty,
                $partitionKeyProperty,
                $start,
            ))

            ->andWhere(\sprintf(
                'root.%s.%s < %s',
                $partitionProperty,
                $partitionKeyProperty,
                $end,
            ))

            ->andWhere(\sprintf(
                'root.%s.%s = %s',
                $partitionProperty,
                $partitionLevelProperty,
                $level,
            ))
        ;

        $result = $queryBuilder->getQuery()->getSQL();

        if (\is_array($result)) {
            yield from $result;
        } else {
            yield $result;
        }
    }
}
