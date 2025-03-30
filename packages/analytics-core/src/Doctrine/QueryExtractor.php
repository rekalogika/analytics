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

namespace Rekalogika\Analytics\Doctrine;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ResultSetMapping;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;

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
     * @var array<int,array{mixed,int|string|ParameterType|ArrayParameterType}>
     */
    private array $parameters;

    public function __construct(Query $query)
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

        $bindValues = [];

        foreach ($parameterMappings as $key => $positions) {
            $parameter = $query->getParameter($key);

            if ($parameter === null) {
                throw new LogicException('Parameter not found');
            }

            /** @psalm-suppress MixedAssignment */
            $originalValue = $parameter->getValue();
            /** @psalm-suppress MixedAssignment */
            $processedValue = $query->processParameterValue($originalValue);

            if ($originalValue === $processedValue) {
                $type = $parameter->getType();
            } else {
                $type = ParameterTypeInferer::inferType($processedValue);
            }

            if (
                !\is_string($type)
                && !\is_int($type)
                && !$type instanceof ArrayParameterType
                && !$type instanceof ParameterType
            ) {
                throw new LogicException('Invalid type');
            }

            foreach ($positions as $position) {
                $bindValues[$position] = [$processedValue, $type];
            }
        }

        ksort($bindValues);

        $this->parameters = $bindValues;
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
     * @return array<int,array{mixed,int|string|ParameterType|ArrayParameterType}>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
