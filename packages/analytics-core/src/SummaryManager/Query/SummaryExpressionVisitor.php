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
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison as ORMComparison;
use Doctrine\ORM\Query\Expr\Orx;
use Rekalogika\Analytics\Contracts\Model\ParameterTypeAware;
use Rekalogika\Analytics\Exception\BadMethodCallException;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Exception\LogicException;
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

    private function getFieldType(string $field): mixed
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

            /** @psalm-suppress MixedReturnStatement */
            return $fieldMetadata['type'] ?? null; // @phpstan-ignore-line
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
        $value = $comparison->getValue()->getValue();
        /** @psalm-suppress MixedAssignment */
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
     *
     * @return mixed
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
        $values = $comparison->getValue()->getValue();

        if (!\is_array($values)) {
            throw new LogicException('Value must be an array with IN or NOT IN operator');
        }

        // check if value has null

        $hasNull = \in_array(null, $values, true);
        $valuesWithoutNull = array_values(array_filter($values, fn($v) => $v !== null));

        // transform valuesWithoutNull to database value

        $type = $this->getType($field);

        if ($type !== null) {
            $valuesWithoutNull = $this->transformValuesToDatabaseValues(
                $valuesWithoutNull,
                $type,
            );
        }

        if ($type instanceof ParameterTypeAware) {
            $parameterType = $type->getArrayParameterType();
        } else {
            $parameterType = null;
        }

        // build without null expressions

        $valuesWithoutNullParameter = $this->queryBuilder->createNamedParameter(
            value: $valuesWithoutNull,
            type: $parameterType,
        );

        if ($comparisonOperator === Comparison::NIN) {
            $valuesWithoutNullExpression = $this->queryBuilder->expr()->notIn(
                $fieldWithAlias,
                $valuesWithoutNullParameter,
            );
        } else {
            $valuesWithoutNullExpression = $this->queryBuilder->expr()->in(
                $fieldWithAlias,
                $valuesWithoutNullParameter,
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

    private function getType(string $field): ?Type
    {
        /** @psalm-suppress MixedAssignment */
        $type = $this->getFieldType($field);

        if (!\is_string($type)) {
            return null;
        }

        try {
            return Type::getType($type);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param list<mixed> $values
     * @return list<mixed>
     */
    private function transformValuesToDatabaseValues(
        array $values,
        Type $type,
    ): array {
        $databasePlatform = $this->queryBuilder->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();

        $newValues = array_map(
            fn(mixed $value): mixed => $type->convertToDatabaseValue($value, $databasePlatform),
            $values,
        );

        return $newValues;
    }

    #[\Override]
    public function walkValue(Value $value): mixed
    {
        throw new BadMethodCallException('Not used');
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
