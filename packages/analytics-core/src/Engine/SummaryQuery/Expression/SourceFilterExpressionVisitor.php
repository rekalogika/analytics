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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Expression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Summary\SourceFilterExpression;

/**
 * Applies the filter of a summary to a query against its source entities. The
 * fields of the comparisons are property paths of the source entity.
 *
 * The values are bound without a type, they are constants given in the
 * attribute. A string compared with e.g. a date or a UUID column is
 * converted by the database.
 */
final class SourceFilterExpressionVisitor extends BaseExpressionVisitor
{
    #[\Override]
    public function dispatch(Expression $expr): mixed
    {
        if ($expr instanceof SourceFilterExpression) {
            return $expr->getExpression(new SourceQueryContext(
                queryBuilder: $this->queryBuilder,
                summaryMetadata: $this->summaryMetadata,
            ));
        }

        return parent::dispatch($expr);
    }

    #[\Override]
    public function visitField(Field $field): FieldExpression
    {
        return new FieldExpression(
            $this->queryBuilder->resolve($field->getField()),
            null,
        );
    }

    #[\Override]
    public function walkComparison(Comparison $comparison): mixed
    {
        $pattern = match ($comparison->getOperator()) {
            Comparison::CONTAINS => '%%%s%%',
            Comparison::STARTS_WITH => '%s%%',
            Comparison::ENDS_WITH => '%%%s',
            default => null,
        };

        if ($pattern === null) {
            return parent::walkComparison($comparison);
        }

        /** @psalm-suppress MixedAssignment */
        $value = $comparison->getValue()->visit($this);

        if (!\is_string($value)) {
            throw new LogicException(\sprintf(
                'The value of a "%s" comparison must be a string, got "%s".',
                $comparison->getOperator(),
                get_debug_type($value),
            ));
        }

        $value = \sprintf($pattern, addcslashes($value, '%_\\'));

        return $this->queryBuilder->expr()->like(
            $this->visitField(new Field($comparison->getField()))->getField(),
            $this->queryBuilder->createNamedParameter($value),
        );
    }
}
