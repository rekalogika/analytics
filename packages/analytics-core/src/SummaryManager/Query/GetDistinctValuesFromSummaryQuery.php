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
use Rekalogika\Analytics\Metadata\DimensionMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final class GetDistinctValuesFromSummaryQuery extends AbstractQuery
{
    public function __construct(
        DimensionMetadata $dimensionMetadata,
        EntityManagerInterface $entityManager,
        int $limit,
    ) {
        $summaryMetadata = $dimensionMetadata->getSummaryMetadata();
        $summaryClass = $summaryMetadata->getSummaryClass();
        $dimension = $dimensionMetadata->getSummaryProperty();

        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $summaryClass,
            alias: 'root',
        );

        $simpleQueryBuilder
            ->select("DISTINCT root.$dimension")
            ->setMaxResults($limit);

        parent::__construct($simpleQueryBuilder);
    }

    /**
     * @return list<mixed>
     */
    public function getResult(): array
    {
        return array_values($this->getSimpleQueryBuilder()->getQuery()->getArrayResult());
    }
}
