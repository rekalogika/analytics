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

namespace Rekalogika\Analytics\Engine\Handler\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\Infrastructure\SourceQueryBuilderFactory;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * Gets the lowest partitions containing the source entities that refer to
 * the given related entities through a path, e.g. the orders whose
 * `customer.country` is one of the given countries.
 *
 * The filter of the summary is not applied. A change to a related
 * entity can move a source entity out of the filter, its partition must be
 * refreshed too.
 */
final class ReferringSourcePartitionsQuery extends AbstractQuery
{
    private readonly int $lowestLevel;

    /**
     * @param string $reversePath The path from the source entity to the
     * related entities.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $summaryMetadata,
        string $reversePath,
    ) {
        $simpleQueryBuilder = SourceQueryBuilderFactory::create(
            entityManager: $entityManager,
            summaryMetadata: $summaryMetadata,
            filtered: false,
        );

        parent::__construct($simpleQueryBuilder);

        $partitionMetadata = $summaryMetadata->getPartition();
        $partitionClass = $partitionMetadata->getPartitionClass();
        $this->lowestLevel = PartitionUtil::getLowestLevel($partitionClass);

        $classifier = $partitionClass::getClassifierExpression(
            input: $partitionMetadata->getSource(),
            level: $this->lowestLevel,
            context: new SourceQueryContext(
                queryBuilder: $simpleQueryBuilder,
                summaryMetadata: $summaryMetadata,
                partitionMetadata: $partitionMetadata,
            ),
        );

        $simpleQueryBuilder
            ->select(\sprintf('DISTINCT %s AS par_key', $classifier))
            ->andWhere(\sprintf(
                '%s IN (:entities)',
                $simpleQueryBuilder->resolve($reversePath),
            ));
    }

    /**
     * @param list<object> $entities
     * @return list<Partition>
     */
    public function getPartitions(array $entities): array
    {
        if ($entities === []) {
            return [];
        }

        /** @var list<int|string|null> */
        $keys = $this->getSimpleQueryBuilder()
            ->setParameter('entities', $entities)
            ->getQuery()
            ->getSingleColumnResult();

        $partitionClass = $this->summaryMetadata->getPartition()->getPartitionClass();
        $partitions = [];

        foreach ($keys as $key) {
            // a source entity without a partitioning key is never summarized

            if ($key === null) {
                continue;
            }

            $partitions[] = $partitionClass::createFromSourceValue($key, $this->lowestLevel);
        }

        return $partitions;
    }
}
