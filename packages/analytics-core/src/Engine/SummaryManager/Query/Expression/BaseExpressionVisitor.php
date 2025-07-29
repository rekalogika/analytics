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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison as ORMComparison;
use Doctrine\ORM\Query\Expr\Orx;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\DatabaseValueAware;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

abstract class BaseExpressionVisitor extends ExpressionVisitor
{
    protected readonly string $rootAlias;

    /**
     * @var array<string,true>
     */
    protected array $involvedDimensions = [];

    /**
     * @var ClassMetadata<object>
     */
    protected readonly ClassMetadata $classMetadata;

    /**
     * @param list<string> $validFields
     */
    final public function __construct(
        protected readonly SimpleQueryBuilder $queryBuilder,
        protected readonly array $validFields,
        protected readonly SummaryMetadata $summaryMetadata,
    ) {
        $this->rootAlias = $this->queryBuilder->getRootAliases()[0]
            ?? throw new LogicException('No root alias found');

        $this->classMetadata = $this->queryBuilder->getEntityManager()
            ->getClassMetadata($this->queryBuilder->getRootEntities()[0]
                ?? throw new LogicException('No root entity found'));
    }

    /**
     * @return list<string>
     */
    public function getInvolvedDimensions(): array
    {
        return array_keys($this->involvedDimensions);
    }

    abstract public function visitField(Field $field): mixed;


    #[\Override]
    public function walkComparison(Comparison $comparison): mixed
    {
        // special case for IN and NOT IN
        $operator = $comparison->getOperator();

        if ($operator === Comparison::IN || $operator === Comparison::NIN) {
            return $this->walkInOrNotInComparison($comparison);
        }

        // visit field
        $field = (new Field($comparison->getField()))->visit($this);

        if (!$field instanceof FieldExpression) {
            throw new UnexpectedValueException('Field must be an instance of FieldExpression');
        }

        // continue for other cases
        /** @psalm-suppress MixedAssignment */
        $value = $comparison->getValue()->visit($this);

        if (\is_array($value)) {
            throw new LogicException('Value cannot be an array');
        }

        // special case for eq null
        if ($operator === Comparison::EQ && $value === null) {
            return \sprintf(
                'REKALOGIKA_IS_NULL(%s) = TRUE',
                $field->getField(),
            );
        }

        // special case for neq null
        if ($operator === Comparison::NEQ && $value === null) {
            return \sprintf(
                'REKALOGIKA_IS_NOT_NULL(%s) = TRUE',
                $field->getField(),
            );
        }

        // create named param
        $param = [
            'value' => $value,
            'type' => $field->getType(),
        ];

        // unit enum special case
        if ($value instanceof \UnitEnum) {
            unset($param['type']);
        }

        /** @psalm-suppress MixedArgument */
        $value = $this->queryBuilder->createNamedParameter(...$param);

        return match ($operator) {
            Comparison::EQ => $this->queryBuilder->expr()->eq($field->getField(), $value),
            Comparison::NEQ => $this->queryBuilder->expr()->neq($field->getField(), $value),
            Comparison::LT => $this->queryBuilder->expr()->lt($field->getField(), $value),
            Comparison::LTE => $this->queryBuilder->expr()->lte($field->getField(), $value),
            Comparison::GT => $this->queryBuilder->expr()->gt($field->getField(), $value),
            Comparison::GTE => $this->queryBuilder->expr()->gte($field->getField(), $value),
            default => throw new InvalidArgumentException(\sprintf(
                'Invalid operator "%s", valid operators are: %s',
                $operator,
                implode(', ', [
                    Comparison::EQ,
                    Comparison::NEQ,
                    Comparison::LT,
                    Comparison::LTE,
                    Comparison::GT,
                    Comparison::GTE,
                    Comparison::IN,
                    Comparison::NIN,
                ]),
            )),
        };
    }

    /**
     * convert "field IN (null, a, b, c)" to "(field IN (a, b, c) OR field IS NULL)"
     * and convert "field NOT IN (null, a, b, c)" to "(field NOT IN (a, b, c) AND
     * field IS NOT NULL)"
     */
    private function walkInOrNotInComparison(Comparison $comparison): mixed
    {
        // visit field
        $field = (new Field($comparison->getField()))->visit($this);

        if (!$field instanceof FieldExpression) {
            throw new UnexpectedValueException('Field must be an instance of FieldExpression');
        }

        // comparison operator, make sure in or not in
        $comparisonOperator = $comparison->getOperator();

        if ($comparisonOperator !== Comparison::IN && $comparisonOperator !== Comparison::NIN) {
            throw new LogicException('Invalid operator for IN or NOT IN');
        }

        // ensure value is array

        /** @psalm-suppress MixedAssignment */
        $values = $comparison->getValue()->visit($this);

        if (!\is_array($values)) {
            throw new LogicException('Value must be an array with IN or NOT IN operator');
        }

        // check if value has null
        $hasNull = \in_array(null, $values, true);
        $valuesWithoutNull = array_values(array_filter($values, fn($v): bool => $v !== null));

        // if the condition is now empty
        if ($valuesWithoutNull === []) {
            if ($hasNull) {
                // if empty condition and has null condition, return the null
                // condition

                if ($comparisonOperator === Comparison::NIN) {
                    return \sprintf(
                        'REKALOGIKA_IS_NOT_NULL(%s) = TRUE',
                        $field->getField(),
                    );
                } else {
                    return \sprintf(
                        'REKALOGIKA_IS_NULL(%s) = TRUE',
                        $field->getField(),
                    );
                }
            } else {
                // if condition is empty, and no null condition either, return
                // false expression

                return $this->queryBuilder->expr()->eq(1, 2);
            }
        }

        // transform valuesWithoutNull to string of named parameters

        $valuesWithoutNullParameters = $this->queryBuilder
            ->createArrayNamedParameter(
                values: $valuesWithoutNull,
                type: $field->getType(),
            );

        if ($comparisonOperator === Comparison::NIN) {
            $valuesWithoutNullExpression = $this->queryBuilder->expr()->notIn(
                $field->getField(),
                $valuesWithoutNullParameters,
            );
        } else {
            $valuesWithoutNullExpression = $this->queryBuilder->expr()->in(
                $field->getField(),
                $valuesWithoutNullParameters,
            );
        }

        // if without null, return the expression
        if (!$hasNull) {
            return $valuesWithoutNullExpression;
        }

        // if has null, add the null expression

        if ($comparisonOperator === Comparison::NIN) {
            return $this->queryBuilder->expr()->andX(
                $valuesWithoutNullExpression,
                \sprintf(
                    'REKALOGIKA_IS_NOT_NULL(%s) = TRUE',
                    $field->getField(),
                ),
            );
        } else {
            return $this->queryBuilder->expr()->orX(
                $valuesWithoutNullExpression,
                \sprintf(
                    'REKALOGIKA_IS_NULL(%s) = TRUE',
                    $field->getField(),
                ),
            );
        }
    }

    #[\Override]
    public function walkValue(Value $value): mixed
    {
        /** @psalm-suppress MixedAssignment */
        $value = $value->getValue();

        if ($value instanceof DatabaseValueAware) {
            return $value->getDatabaseValue();
        }

        if (\is_array($value)) {
            return array_map(
                fn($v): mixed => (new Value($v))->visit($this),
                $value,
            );
        }

        return $value;
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        /**
         * @var list<ORMComparison|Andx|Orx|string>
         */
        $expressions = array_map(
            fn($expression): mixed => $expression->visit($this),
            $expr->getExpressionList(),
        );

        return match ($expr->getType()) {
            CompositeExpression::TYPE_AND => $this->queryBuilder->expr()->andX(...$expressions),
            CompositeExpression::TYPE_OR => $this->queryBuilder->expr()->orX(...$expressions),
            CompositeExpression::TYPE_NOT => $this->queryBuilder->expr()->not(...$expressions),
            default => throw new InvalidArgumentException(\sprintf(
                'Invalid composite expression type "%s", valid types are: %s',
                $expr->getType(),
                implode(', ', [
                    CompositeExpression::TYPE_AND,
                    CompositeExpression::TYPE_OR,
                    CompositeExpression::TYPE_NOT,
                ]),
            )),
        };
    }
}
