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

namespace Rekalogika\Analytics\Engine\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final readonly class ExpressionUtil
{
    private function __construct() {}

    /**
     * @template T of BaseExpressionVisitor
     * @param class-string<T> $visitorClass
     * @return T
     */
    public static function addExpressionToQueryBuilder(
        SummaryMetadata $metadata,
        SimpleQueryBuilder $queryBuilder,
        Expression $expression,
        string $visitorClass,
    ): BaseExpressionVisitor {
        $validDimensions = array_values(array_filter(
            array_keys($metadata->getLeafDimensions()),
            fn(string $dimension): bool => $dimension !== '@values',
        ));

        $visitor = new $visitorClass(
            queryBuilder: $queryBuilder,
            validFields: $validDimensions,
            summaryMetadata: $metadata,
        );

        /** @psalm-suppress MixedAssignment */
        $expression = $visitor->dispatch($expression);

        // @phpstan-ignore argument.type
        $queryBuilder->andWhere($expression);

        return $visitor;
    }
}
