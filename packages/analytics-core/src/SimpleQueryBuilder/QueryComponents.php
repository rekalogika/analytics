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
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Utility\HierarchyDiscriminatorResolver;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;

/**
 * Extracts information from Doctrine ORM Query objects
 */
final class QueryComponents
{
    /**
     * @var list<string>|null
     */
    private ?array $sqlStatements = null;

    private ?ResultSetMapping $resultSetMapping = null;

    private ?ParserResult $parserResult = null;

    /**
     * @var list{list<mixed>,array<array-key, mixed>}
     */
    private ?array $parametersAndTypes = null;

    /**
     * @var null|array<int<0,max>,mixed>
     */
    private ?array $parameters = null;

    /**
     * @var null|array<int<0,max>,int|string|ParameterType|ArrayParameterType>
     */
    private ?array $types = null;

    public function __construct(private Query $query) {}

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
     * @phpstan-ignore missingType.generics
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return list<string>
     */
    public function getSqlStatements(): array
    {
        if ($this->sqlStatements !== null) {
            return $this->sqlStatements;
        }

        $sqlExecutor = $this->getParserResult()->prepareSqlExecutor($this->query);
        $sqlStatements = $sqlExecutor->getSqlStatements();

        if (\is_string($sqlStatements)) {
            $sqlStatements = [$sqlStatements];
        }

        return $this->sqlStatements = $sqlStatements;
    }

    private function getParserResult(): ParserResult
    {
        if ($this->parserResult !== null) {
            return $this->parserResult;
        }

        $parser = new Parser($this->getQuery());
        $parserResult = $parser->parse();

        return $this->parserResult = $parserResult;
    }

    public function getSqlStatement(): string
    {
        $sqlStatements = $this->getSqlStatements();

        if (\count($sqlStatements) !== 1) {
            throw new UnexpectedValueException('Expected exactly one SQL statement');
        }

        return $sqlStatements[0];
    }

    public function getResultSetMapping(): ResultSetMapping
    {
        if ($this->resultSetMapping !== null) {
            return $this->resultSetMapping;
        }

        return $this->resultSetMapping = $this->getParserResult()->getResultSetMapping();
    }

    /**
     * @return array{list<mixed>,array<array-key,mixed>}
     */
    private function getParametersAndTypes(): array
    {
        if ($this->parametersAndTypes !== null) {
            return $this->parametersAndTypes;
        }

        $parameterMappings = $this->getParserResult()->getParameterMappings();

        $parametersAndTypes = $this->processParameterMappings(
            $parameterMappings,
            $this->getQuery(),
        );

        return $this->parametersAndTypes = $parametersAndTypes;
    }

    /**
     * @return array<int<0,max>,mixed>
     */
    public function getParameters(): array
    {
        if ($this->parameters !== null) {
            return $this->parameters;
        }

        [$parameters,] = $this->getParametersAndTypes();

        return $this->parameters = $parameters;
    }

    /**
     * @return array<int<0,max>,int|string|ParameterType|ArrayParameterType>
     */
    public function getTypes(): array
    {
        if ($this->types !== null) {
            return $this->types;
        }

        [, $types] = $this->getParametersAndTypes();

        /** @var array<int<0,max>,int|string|ParameterType|ArrayParameterType> $types */

        /** @psalm-suppress MixedReturnTypeCoercion */
        /** @psalm-suppress MixedPropertyTypeCoercion */
        return $this->types = $types;
    }
}
