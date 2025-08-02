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

namespace Rekalogika\Analytics\Tests\UnitTests\PartitionExpressionResolver;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

final class SqlExpressionVisitor extends ExpressionVisitor
{
    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        $operator = $comparison->getOperator();
        $value = $this->walkValue($comparison->getValue());

        switch ($operator) {
            case Comparison::EQ:
                return "$field = $value";
            case Comparison::NEQ:
                return "$field <> $value";
            case Comparison::GT:
                return "$field > $value";
            case Comparison::GTE:
                return "$field >= $value";
            case Comparison::LT:
                return "$field < $value";
            case Comparison::LTE:
                return "$field <= $value";
            case Comparison::IN:
                return "$field IN ($value)";
            case Comparison::NIN:
                return "$field NOT IN ($value)";
            default:
                throw new \InvalidArgumentException("Unknown operator: $operator");
        }
    }

    #[\Override]
    public function walkValue(Value $value): string
    {
        /** @var string|list<string> */
        $val = $value->getValue();

        if (\is_array($val)) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            return implode(', ', array_map($this->quote(...), $val));
        }

        /** @psalm-suppress MixedReturnStatement */
        return $this->quote($val);
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $type = $expr->getType();

        /** @var list<string> */
        $parts = array_map(
            fn($e): mixed => $e->visit($this),
            $expr->getExpressionList(),
        );

        return match ($type) {
            CompositeExpression::TYPE_AND => '(' . implode(' AND ', $parts) . ')',
            CompositeExpression::TYPE_OR => '(' . implode(' OR ', $parts) . ')',
            CompositeExpression::TYPE_NOT => 'NOT (' . implode(' AND ', $parts) . ')',
            default => throw new \InvalidArgumentException("Unknown composite expression type: $type"),
        };
    }

    private function quote(int|string $value): string
    {
        if (is_numeric($value)) {
            return (string) $value;
        }
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
