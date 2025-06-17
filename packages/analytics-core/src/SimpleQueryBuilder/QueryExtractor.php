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

namespace Rekalogika\Analytics\SimpleQueryBuilder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Utility\HierarchyDiscriminatorResolver;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;

/**
 * Extracts information from Doctrine ORM Query objects
 */
final readonly class QueryExtractor
{
    /**
     * @var list<string>
     */
    private array $sqlStatements;

    private ResultSetMapping $resultSetMapping;

    /**
     * @var array<int<0,max>,mixed>
     */
    private array $parameters;

    /**
     * @var array<int<0,max>,int|string|ParameterType|ArrayParameterType>
     */
    private array $types;

    public function __construct(DoctrineQuery $query)
    {
        $parser = new Parser($query);
        $parserResult = $parser->parse();
        $sqlExecutor = $parserResult->prepareSqlExecutor($query);
        $sqlStatements = $sqlExecutor->getSqlStatements();

        if (\is_string($sqlStatements)) {
            $this->sqlStatements = [$sqlStatements];
        } else {
            $this->sqlStatements = $sqlStatements;
        }

        $this->resultSetMapping = $parserResult->getResultSetMapping();
        $parameterMappings = $parserResult->getParameterMappings();

        [$parameters, $types] = $this->processParameterMappings(
            $parameterMappings,
            $query,
        );

        $this->parameters = $parameters;
        /**
         * @psalm-suppress MixedPropertyTypeCoercion
         * @phpstan-ignore assign.propertyType
         */
        $this->types = $types;
    }

    /**
     * @return mixed[] tuple of (value, type)
     * @phpstan-return array{0: mixed, 1: mixed}
     */
    private function resolveParameterValue(Parameter $parameter, Query $query): array
    {
        if ($parameter->typeWasSpecified()) {
            return [$parameter->getValue(), $parameter->getType()];
        }

        $key           = $parameter->getName();
        /** @psalm-suppress MixedAssignment */
        $originalValue = $parameter->getValue();
        /** @psalm-suppress MixedAssignment */
        $value         = $originalValue;
        $rsm           = $this->getResultSetMapping();

        if ($value instanceof ClassMetadata && isset($rsm->metadataParameterMapping[$key])) {
            /** @psalm-suppress MixedAssignment */
            $value = $value->getMetadataValue($rsm->metadataParameterMapping[$key]);
        }

        if ($value instanceof ClassMetadata && isset($rsm->discriminatorParameters[$key])) {
            /**
             * @psalm-suppress InternalClass
             * @psalm-suppress InternalMethod
             * @phpstan-ignore staticMethod.internalClass
             */
            $value = array_keys(HierarchyDiscriminatorResolver::resolveDiscriminatorsForClass($value, $query->getEntityManager()));
        }

        /** @psalm-suppress MixedAssignment */
        $processedValue = $query->processParameterValue($value);

        return [
            $processedValue,
            $originalValue === $processedValue
                ? $parameter->getType()
                : ParameterTypeInferer::inferType($processedValue),
        ];
    }

    /**
     * @see Doctrine\ORM\Query::processParameterMappings()
     *
     * @param array<list<int>> $paramMappings
     * @return array{list<mixed>,array<array-key,mixed>}
     */
    private function processParameterMappings(
        array $paramMappings,
        Query $query,
    ): array {
        $parameters = $query->getParameters();

        $sqlParams = [];
        $types     = [];

        foreach ($parameters as $parameter) {
            $key = $parameter->getName();

            if (! isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            /** @psalm-suppress MixedAssignment */
            [$value, $type] = $this->resolveParameterValue($parameter, $query);

            foreach ($paramMappings[$key] as $position) {
                /** @psalm-suppress MixedAssignment */
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];

            // optimized multi value sql positions away for now,
            // they are not allowed in DQL anyways.
            $value      = [$value];
            $countValue = \count($value);

            for ($i = 0, $l = \count($sqlPositions); $i < $l; $i++) {
                /** @psalm-suppress MixedAssignment */
                $sqlParams[$sqlPositions[$i]] = $value[$i % $countValue];
            }
        }

        if (\count($sqlParams) !== \count($types)) {
            throw QueryException::parameterTypeMismatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return [$sqlParams, $types];
    }

    /**
     * @return list<string>
     */
    public function getSqlStatements(): array
    {
        return $this->sqlStatements;
    }

    public function getSqlStatement(): string
    {
        if (\count($this->sqlStatements) !== 1) {
            throw new UnexpectedValueException('Expected exactly one SQL statement');
        }

        return $this->sqlStatements[0];
    }

    public function getResultSetMapping(): ResultSetMapping
    {
        return $this->resultSetMapping;
    }

    /**
     * @return array<int<0,max>,mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<int<0,max>,int|string|ParameterType|ArrayParameterType>
     */
    public function getTypes(): array
    {
        // @phpstan-ignore return.type
        return $this->types;
    }
}
