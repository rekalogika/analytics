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
use Rekalogika\Analytics\Partition;

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
        $partitionProperty = $partitionMetadata->getSummaryProperty();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

        $level = $this->start->getLevel();

        if ($level !== $this->end->getLevel()) {
            throw new \InvalidArgumentException('Start and end partitions must have the same level');
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
                $this->quoteVariable($start),
            ))

            ->andWhere(\sprintf(
                'root.%s.%s < %s',
                $partitionProperty,
                $partitionKeyProperty,
                $this->quoteVariable($end),
            ))

            ->andWhere(\sprintf(
                'root.%s.%s = %s',
                $partitionProperty,
                $partitionLevelProperty,
                $this->quoteVariable($level),
            ))
        ;

        $result = $queryBuilder->getQuery()->getSQL();

        if (\is_array($result)) {
            yield from $result;
        } else {
            yield $result;
        }
    }

    private function quoteVariable(int|string $input): string
    {
        if (\is_string($input)) {
            return \sprintf("'%s'", $input);
        }

        return (string) $input;
    }
}
