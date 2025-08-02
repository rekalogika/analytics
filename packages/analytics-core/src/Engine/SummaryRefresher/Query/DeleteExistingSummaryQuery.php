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

namespace Rekalogika\Analytics\Engine\SummaryRefresher\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherQuery;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final class DeleteExistingSummaryQuery extends AbstractQuery implements SummaryRefresherQuery
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $summaryMetadata,
    ) {
        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $summaryMetadata->getSummaryClass(),
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $this->prepare();
    }

    #[\Override]
    public function withBoundary(Partition $start, Partition $end): static
    {
        $clone = clone $this;

        $lowerBound = $start->getLowerBound();
        $upperBound = $end->getUpperBound();
        $level = $start->getLevel();

        if ($level !== $end->getLevel()) {
            throw new InvalidArgumentException(\sprintf(
                'The start and end partitions must be on the same level, but got "%d" and "%d"',
                $start->getLevel(),
                $end->getLevel(),
            ));
        }

        $clone->getSimpleQueryBuilder()
            ->setParameter('lowerBound', $lowerBound)
            ->setParameter('upperBound', $upperBound)
            ->setParameter('lowerLevel', $level);

        return $clone;
    }

    #[\Override]
    public function getQueries(): iterable
    {
        $query = $this->getSimpleQueryBuilder()->getQuery();

        yield DecomposedQuery::createFromQuery($query);
    }

    private function prepare(): void
    {
        $summaryClassName = $this->summaryMetadata->getSummaryClass();
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $partitionProperty = $partitionMetadata->getName();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

        $this->getSimpleQueryBuilder()
            ->delete($summaryClassName, 'root');

        $this->getSimpleQueryBuilder()
            ->andWhere(\sprintf(
                'root.%s.%s >= :lowerBound',
                $partitionProperty,
                $partitionKeyProperty,
            ))

            ->andWhere(\sprintf(
                'root.%s.%s < :upperBound',
                $partitionProperty,
                $partitionKeyProperty,
            ))

            ->andWhere(\sprintf(
                'root.%s.%s = :lowerLevel',
                $partitionProperty,
                $partitionLevelProperty,
            ))
        ;

        $this->getSimpleQueryBuilder()
            ->setParameter('lowerBound', '(placeholder) the lower bound')
            ->setParameter('upperBound', '(placeholder) the upper bound')
            ->setParameter('lowerLevel', '(placeholder) the lower level');
    }
}
