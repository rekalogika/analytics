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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison as ORMComparison;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

final class SummaryExpressionVisitor extends ExpressionVisitor
{
    private string $rootAlias;

    /**
     * @var array<string,true>
     */
    private array $involvedDimensions = [];

    /**
     * @var ClassMetadata<object>
     */
    private ClassMetadata $classMetadata;

    /**
     * @param list<string> $validFields
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly array $validFields,
        private readonly QueryContext $queryContext,
    ) {
        $this->rootAlias = $this->queryBuilder->getRootAliases()[0]
            ?? throw new \InvalidArgumentException('No root alias found');

        $this->classMetadata = $this->queryBuilder->getEntityManager()
            ->getClassMetadata($this->queryBuilder->getRootEntities()[0]
                ?? throw new \InvalidArgumentException('No root entity found'));
    }

    /**
     * @return list<string>
     */
    public function getInvolvedDimensions(): array
    {
        return array_keys($this->involvedDimensions);
    }

    private function getFieldType(string $field, mixed $value): mixed
    {
        if (is_array($value)) {
            return null;
        }

        if (!\in_array($field, $this->validFields, true)) {
            throw new \InvalidArgumentException("Invalid dimension: $field");
        }

        if ($this->classMetadata->hasAssociation($field)) {
            return null;
        } elseif ($this->classMetadata->hasField($field)) {
            $fieldMetadata = $this->classMetadata->getFieldMapping($field);

            /** @psalm-suppress MixedReturnStatement */
            return $fieldMetadata['type'] ?? null; // @phpstan-ignore-line
        } else {
            return null;
        }
    }

    #[\Override]
    public function walkComparison(Comparison $comparison): mixed
    {
        $field = $comparison->getField();

        if (!\in_array($field, $this->validFields, true)) {
            throw new \InvalidArgumentException("Invalid dimension: $field");
        }

        $this->involvedDimensions[$field] = true;
        $fieldWithAlias = $this->rootAlias . '.' . $field;

        /** @psalm-suppress MixedAssignment */
        $value = $comparison->getValue()->getValue();
        /** @psalm-suppress MixedAssignment */
        $type = $this->getFieldType($field, $value);

        dump($value, $type);

        /**
         * @psalm-suppress MixedArgument
         */
        $value = $this->queryContext->createNamedParameter(
            value: $value,
            // @phpstan-ignore argument.type
            type: $type,
        );

        $operator = $comparison->getOperator();

        return match ($operator) {
            Comparison::EQ => $this->queryBuilder->expr()->eq($fieldWithAlias, $value),
            Comparison::NEQ => $this->queryBuilder->expr()->neq($fieldWithAlias, $value),
            Comparison::LT => $this->queryBuilder->expr()->lt($fieldWithAlias, $value),
            Comparison::LTE => $this->queryBuilder->expr()->lte($fieldWithAlias, $value),
            Comparison::GT => $this->queryBuilder->expr()->gt($fieldWithAlias, $value),
            Comparison::GTE => $this->queryBuilder->expr()->gte($fieldWithAlias, $value),
            Comparison::IN => $this->queryBuilder->expr()->in($fieldWithAlias, $value),
            Comparison::NIN => $this->queryBuilder->expr()->notIn($fieldWithAlias, $value),
            default => throw new \InvalidArgumentException("Unknown operator: $operator"),
        };
    }

    #[\Override]
    public function walkValue(Value $value): mixed
    {
        throw new \BadMethodCallException('Not used');
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        /**
         * @var list<ORMComparison|Andx|Orx|string>
         */
        $expressions = array_map(
            fn($expression): mixed => $this->dispatch($expression),
            $expr->getExpressionList(),
        );

        return match ($expr->getType()) {
            CompositeExpression::TYPE_AND => $this->queryBuilder->expr()->andX(...$expressions),
            CompositeExpression::TYPE_OR => $this->queryBuilder->expr()->orX(...$expressions),
            default => throw new \InvalidArgumentException("Unknown composite expression type: {$expr->getType()}"),
        };
    }
}
