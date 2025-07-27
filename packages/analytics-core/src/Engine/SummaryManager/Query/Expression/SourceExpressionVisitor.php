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

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;

final class SourceExpressionVisitor extends BaseExpressionVisitor
{
    /**
     * @see RollUpSourceToSummaryPerSourceQuery::processDimensions()
     */
    #[\Override]
    public function visitField(Field $field): FieldExpression
    {
        $fieldName = $field->getField();

        $dimensionMetadata = $this->summaryMetadata->getDimension($fieldName);
        $valueResolver = $dimensionMetadata->getValueResolver();

        $expression = $valueResolver->getExpression(
            context: new SourceQueryContext(
                queryBuilder: $this->queryBuilder,
                summaryMetadata: $this->summaryMetadata,
                dimensionMetadata: $dimensionMetadata,
            ),
        );

        return new FieldExpression($expression, null);
    }
}
