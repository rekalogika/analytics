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

namespace Rekalogika\Analytics\Engine\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\ExpressionUtil;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\SourceFilterExpressionVisitor;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

/**
 * Creates the query builders of the queries against the source entities of a
 * summary, with the alias `root`, and with the filter of the summary applied.
 * Every query against the source entities must be created here, so that none
 * of them forgets the filter.
 */
final readonly class SourceQueryBuilderFactory
{
    private function __construct() {}

    /**
     * @param bool $filtered Whether to apply the filter of the summary. A
     * query looking for the source entities affected by a change must not
     * apply it: the change can move a source entity out of the filter, its
     * partition must be refreshed too.
     */
    public static function create(
        EntityManagerInterface $entityManager,
        SummaryMetadata $summaryMetadata,
        bool $filtered = true,
    ): SimpleQueryBuilder {
        $queryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $summaryMetadata->getSourceClass(),
            alias: 'root',
        );

        $filter = $summaryMetadata->getFilter();

        if ($filtered && $filter !== null) {
            ExpressionUtil::addExpressionToQueryBuilder(
                metadata: $summaryMetadata,
                queryBuilder: $queryBuilder,
                expression: $filter,
                visitorClass: SourceFilterExpressionVisitor::class,
            );
        }

        return $queryBuilder;
    }
}
