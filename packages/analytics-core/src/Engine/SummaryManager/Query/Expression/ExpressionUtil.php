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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final readonly class ExpressionUtil
{
    private function __construct() {}

    public static function addExpressionToSummaryQueryBuilder(
        SummaryMetadata $metadata,
        SimpleQueryBuilder $queryBuilder,
        Expression $expression,
    ): SummaryExpressionVisitor {
        $validDimensions = array_values(array_filter(
            array_keys($metadata->getLeafDimensions()),
            fn(string $dimension): bool => $dimension !== '@values',
        ));

        $visitor = new SummaryExpressionVisitor(
            queryBuilder: $queryBuilder,
            validFields: $validDimensions,
        );

        /** @psalm-suppress MixedAssignment */
        $expression = $visitor->dispatch($expression);

        // @phpstan-ignore argument.type
        $queryBuilder->andWhere($expression);

        return $visitor;
    }
}
