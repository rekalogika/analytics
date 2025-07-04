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
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @api
 */
class HtmlRendererExpressionVisitor extends ExpressionVisitor
{
    public function __construct(
        private Htmlifier $htmlifier,
        private SummaryMetadata $summaryMetadata,
        private TranslatorInterface $translator,
    ) {}

    #[\Override]
    public function walkComparison(Comparison $comparison): string
    {
        return \sprintf(
            '%s %s %s',
            htmlspecialchars($this->walkDimension($comparison->getField())),
            htmlspecialchars($this->walkOperator($comparison->getOperator())),
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
            return sprintf(
                '%s %s',
                $this->walkCompositeExpressionType($expr->getType()),
                implode(' ', $parts)
            );
        }

        if (\count($parts) === 1) {
            return sprintf(
                '%s %s',
                $this->walkCompositeExpressionType($expr->getType()),
                $parts[0]
            );
            return implode(' ' . htmlspecialchars($expr->getType()) . ' ', $parts);
        } else {
            return '(' . implode(' ' . htmlspecialchars($expr->getType()) . ' ', $parts) . ')';
        }
    }

    protected function walkOperator(string $operator): string
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

    protected function walkCompositeExpressionType(string $type): string
    {
        $translatable = match ($type) {
            CompositeExpression::TYPE_AND => new TranslatableMessage('AND'),
            CompositeExpression::TYPE_OR => new TranslatableMessage('OR'),
            CompositeExpression::TYPE_NOT => new TranslatableMessage('NOT'),
            default => throw new LogicException('Unsupported composite expression type: ' . $type),
        };

        return $translatable->trans($this->translator);
    }

    protected function walkDimension(string $field): string
    {
        $dimension = $this->summaryMetadata->getDimension($field);

        return $this->htmlifier->toHtml($dimension->getLabel()->getRootToLeaf());
    }
}
