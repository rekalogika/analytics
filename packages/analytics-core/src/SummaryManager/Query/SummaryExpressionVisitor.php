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

    #[\Override]
    public function walkComparison(Comparison $comparison): mixed
    {
        $field = $comparison->getField();

        if (!\in_array($field, $this->validFields, true)) {
            throw new \InvalidArgumentException("Invalid dimension: $field");
        }

        $fieldMetadata = $this->classMetadata->getFieldMapping($field);

        $this->involvedDimensions[$field] = true;
        $field = $this->rootAlias . '.' . $field;

        /**
         * @psalm-suppress MixedArgument
         * @phpstan-ignore argument.type
         */
        $value = $this->queryContext->createNamedParameter(value: $comparison->getValue()->getValue(), type: $fieldMetadata['type'] ?? null);

        $operator = $comparison->getOperator();

        return match ($operator) {
            Comparison::EQ => $this->queryBuilder->expr()->eq($field, $value),
            Comparison::NEQ => $this->queryBuilder->expr()->neq($field, $value),
            Comparison::LT => $this->queryBuilder->expr()->lt($field, $value),
            Comparison::LTE => $this->queryBuilder->expr()->lte($field, $value),
            Comparison::GT => $this->queryBuilder->expr()->gt($field, $value),
            Comparison::GTE => $this->queryBuilder->expr()->gte($field, $value),
            Comparison::IN => $this->queryBuilder->expr()->in($field, $value),
            Comparison::NIN => $this->queryBuilder->expr()->notIn($field, $value),
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
