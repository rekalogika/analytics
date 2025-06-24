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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison as ORMComparison;
use Doctrine\ORM\Query\Expr\Orx;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Model\DatabaseValueAware;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final class SummaryExpressionVisitor extends ExpressionVisitor
{
    private readonly string $rootAlias;

    /**
     * @var array<string,true>
     */
    private array $involvedDimensions = [];

    /**
     * @var ClassMetadata<object>
     */
    private readonly ClassMetadata $classMetadata;

    /**
     * @param list<string> $validFields
     */
    public function __construct(
        private readonly SimpleQueryBuilder $queryBuilder,
        private readonly array $validFields,
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

    private function getFieldType(string $field): ?string
    {
        if (!\in_array($field, $this->validFields, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid field "%s", valid fields are: %s',
                $field,
                implode(', ', $this->validFields),
            ));
        }

        if ($this->classMetadata->hasAssociation($field)) {
            return null;
        } elseif ($this->classMetadata->hasField($field)) {
            $fieldMetadata = $this->classMetadata->getFieldMapping($field);

            $result = $fieldMetadata['type'] ?? null;

            if ($result === null) {
                throw new InvalidArgumentException(\sprintf(
                    'Field "%s" does not have a type defined in the class metadata',
                    $field,
                ));
            }

            if (!\is_string($result)) {
                throw new InvalidArgumentException(\sprintf(
                    'Field "%s" type must be a string, got "%s"',
                    $field,
                    get_debug_type($result),
                ));
            }

            return $result;
        } else {
            return null;
        }
    }

    #[\Override]
    public function walkComparison(Comparison $comparison): mixed
    {
        // check field

        $field = $comparison->getField();

        if (!\in_array($field, $this->validFields, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid field "%s", valid fields are: %s',
                $field,
                implode(', ', $this->validFields),
            ));
        }

        $this->involvedDimensions[$field] = true;

        // special case for IN and NOT IN

        $operator = $comparison->getOperator();

        if ($operator === Comparison::IN || $operator === Comparison::NIN) {
            return $this->walkInOrNotInComparison($comparison);
        }

        // continue for other cases

        $fieldWithAlias = $this->rootAlias . '.' . $field;

        /** @psalm-suppress MixedAssignment */
        $value = $this->walkValue($comparison->getValue());
        $type = $this->getFieldType($field);

        if (\is_array($value)) {
            throw new LogicException('Value cannot be an array');
        }

        // special case for eq null

        if ($operator === Comparison::EQ && $value === null) {
            return $this->queryBuilder->expr()->isNull($fieldWithAlias);
        }

        // special case for neq null

        if ($operator === Comparison::NEQ && $value === null) {
            return $this->queryBuilder->expr()->isNotNull($fieldWithAlias);
        }

        // create named param

        $param = [
            'value' => $value,
            'type' => $type,
        ];

        if ($value instanceof \UnitEnum) {
            unset($param['type']);
        }

        /** @psalm-suppress MixedArgument */
        $value = $this->queryBuilder->createNamedParameter(...$param);

        return match ($operator) {
            Comparison::EQ => $this->queryBuilder->expr()->eq($fieldWithAlias, $value),
            Comparison::NEQ => $this->queryBuilder->expr()->neq($fieldWithAlias, $value),
            Comparison::LT => $this->queryBuilder->expr()->lt($fieldWithAlias, $value),
            Comparison::LTE => $this->queryBuilder->expr()->lte($fieldWithAlias, $value),
            Comparison::GT => $this->queryBuilder->expr()->gt($fieldWithAlias, $value),
            Comparison::GTE => $this->queryBuilder->expr()->gte($fieldWithAlias, $value),
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
        $field = $comparison->getField();
        $fieldWithAlias = $this->rootAlias . '.' . $field;

        // comparison operator, make sure in or not in

        $comparisonOperator = $comparison->getOperator();

        if ($comparisonOperator !== Comparison::IN && $comparisonOperator !== Comparison::NIN) {
            throw new LogicException('Invalid operator for IN or NOT IN');
        }

        // ensure value is array

        /** @psalm-suppress MixedAssignment */
        $values = $this->walkValue($comparison->getValue());

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
                    return $this->queryBuilder->expr()->isNotNull($fieldWithAlias);
                } else {
                    return $this->queryBuilder->expr()->isNull($fieldWithAlias);
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
                type: $this->getFieldType($field),
            );

        if ($comparisonOperator === Comparison::NIN) {
            $valuesWithoutNullExpression = $this->queryBuilder->expr()->notIn(
                $fieldWithAlias,
                $valuesWithoutNullParameters,
            );
        } else {
            $valuesWithoutNullExpression = $this->queryBuilder->expr()->in(
                $fieldWithAlias,
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
                $this->queryBuilder->expr()->isNotNull($fieldWithAlias),
            );
        } else {
            return $this->queryBuilder->expr()->orX(
                $valuesWithoutNullExpression,
                $this->queryBuilder->expr()->isNull($fieldWithAlias),
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
                fn($v): mixed => $this->walkValue(new Value($v)),
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
            fn($expression): mixed => $this->dispatch($expression),
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
