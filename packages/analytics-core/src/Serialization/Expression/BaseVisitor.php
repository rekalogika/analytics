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

namespace Rekalogika\Analytics\Serialization\Expression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;

/**
 * Base visitor for serialization and deserialization of expressions.
 */
abstract class BaseVisitor extends ExpressionVisitor
{
    protected ?string $currentDimension = null;

    /**
     * @param class-string $summaryClass
     */
    final public function __construct(
        protected readonly string $summaryClass,
        protected readonly ValueSerializer $valueSerializer,
    ) {}

    #[\Override]
    public function walkComparison(Comparison $comparison): Comparison
    {
        $dimensionName = $comparison->getField();
        $this->currentDimension = $dimensionName;

        $operator = $comparison->getOperator();
        $value = $comparison->getValue();

        return new Comparison(
            field: $dimensionName,
            op: $operator,
            value: $value->visit($this),
        );
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr): CompositeExpression
    {
        /**
         * @psalm-suppress MixedReturnStatement
         * @var list<Expression>
         */
        $expressions = array_map(
            fn(Expression $e): mixed => $e->visit($this),
            $expr->getExpressionList(),
        );

        return new CompositeExpression(
            type: $expr->getType(),
            expressions: $expressions,
        );
    }
}
