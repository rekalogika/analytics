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

namespace Rekalogika\Analytics\Frontend\Html\Visitor;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final class ExpressionRendererVisitor extends ExpressionVisitor
{
    public function __construct(
        private readonly Htmlifier $htmlifier,
        private readonly SummaryMetadata $summaryMetadata,
    ) {}

    #[\Override]
    public function walkComparison(Comparison $comparison): string
    {
        return \sprintf(
            '%s %s %s',
            $this->walkDimension($comparison->getField()),
            $this->walkOperator($comparison->getOperator()),
            $this->walkValue($comparison->getValue()),
        );
    }

    #[\Override]
    public function walkValue(Value $value): string
    {
        /** @psalm-suppress MixedAssignment */
        $value = $value->getValue();

        if (\is_array($value)) {
            $parts = [];

            /** @psalm-suppress MixedAssignment */
            foreach ($value as $part) {
                $parts[] = $this->walkValue(new Value($part));
            }

            return '(' . implode(', ', $parts) . ')';
        }

        return $this->htmlifier->toHtml($value);
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr): string
    {
        $parts = [];

        foreach ($expr->getExpressionList() as $part) {
            /** @psalm-suppress MixedAssignment */
            $string = $this->dispatch($part);

            if (!\is_string($string)) {
                throw new LogicException('Expected string from expression dispatch, got ' . \gettype($string));
            }

            $parts[] = $string;
        }

        if ($expr->getType() === CompositeExpression::TYPE_NOT) {
            return \sprintf(
                '%s %s',
                $this->walkCompositeExpressionType($expr->getType()),
                implode(' ', $parts),
            );
        }

        if (\count($parts) === 1) {
            return $parts[0];
        }

        $type = $this->walkCompositeExpressionType($expr->getType());

        return '(' . implode(' ' . $type . ' ', $parts) . ')';
    }

    private function walkOperator(string $operator): string
    {
        return match ($operator) {
            Comparison::EQ => '=',
            Comparison::NEQ => '≠',
            Comparison::LT => '<',
            Comparison::LTE => '≤',
            Comparison::GT => '>',
            Comparison::GTE => '≥',
            Comparison::IN => '∈',
            Comparison::NIN => '∉',
            default => throw new LogicException('Unsupported operator: ' . $operator),
        };
    }

    private function walkCompositeExpressionType(string $type): string
    {
        $translatable = match ($type) {
            CompositeExpression::TYPE_AND => new TranslatableMessage('AND'),
            CompositeExpression::TYPE_OR => new TranslatableMessage('OR'),
            CompositeExpression::TYPE_NOT => new TranslatableMessage('NOT'),
            default => throw new LogicException('Unsupported composite expression type: ' . $type),
        };

        return $this->htmlifier->toHtml($translatable);
    }

    private function walkDimension(string $field): string
    {
        $dimension = $this->summaryMetadata->getDimension($field);
        $string = $this->htmlifier->toHtml($dimension->getLabel()->getRootAndLeaf());

        return \sprintf(
            '<u>%s</u>',
            $string,
        );
    }
}
