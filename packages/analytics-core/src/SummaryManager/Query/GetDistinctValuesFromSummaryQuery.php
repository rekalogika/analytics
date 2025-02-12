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

use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Metadata\DimensionMetadata;

final class GetDistinctValuesFromSummaryQuery extends AbstractQuery
{
    public function __construct(
        private readonly DimensionMetadata $dimensionMetadata,
        private readonly QueryBuilder $queryBuilder,
        private readonly int $limit,
    ) {
        parent::__construct($queryBuilder);
    }

    /**
     * @return list<mixed>
     */
    public function getResult(): array
    {
        $summaryMetadata = $this->dimensionMetadata->getSummaryMetadata();
        $summaryClass = $summaryMetadata->getSummaryClass();
        $dimension = $this->dimensionMetadata->getSummaryProperty();

        $queryBuilder = $this->queryBuilder
            ->select("DISTINCT root.$dimension")
            ->from($summaryClass, 'root')
            ->setMaxResults($this->limit);

        // order by is disabled as probably too expensive

        // $orderBy = $this->dimensionMetadata->getOrderBy();

        // if (is_array($orderBy)) {
        //     foreach ($orderBy as $field => $direction) {
        //         $dqlField = $this->getQueryContext()->resolvePath($field);

        //         $queryBuilder->addOrderBy($dqlField, $direction->value);
        //     }
        // } else {
        //     $queryBuilder->addOrderBy('root.' . $dimension, $orderBy->value);
        // }

        return array_values($queryBuilder->getQuery()->getArrayResult());
    }
}
