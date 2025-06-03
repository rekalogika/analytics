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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Contracts\Summary\Context;
use Rekalogika\Analytics\Exception\QueryException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionPropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final class SourceQuery extends AbstractQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $summaryMetadata,
        private readonly Tuple $tuple,
    ) {
        $sourceClasses = $summaryMetadata->getSourceClasses();

        if (\count($sourceClasses) !== 1) {
            throw new QueryException('Source query can only be created for a single source class');
        }

        $sourceClass = reset($sourceClasses);

        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $sourceClass,
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);
    }

    public function getQueryBuilder(): QueryBuilder
    {
        $this->initialize();
        $this->processDimensions();
        $this->processOrderBy();

        return $this->getSimpleQueryBuilder()->getQueryBuilder();
    }

    private function initialize(): void
    {
        $this->getSimpleQueryBuilder()->addSelect('root');
    }

    private function processDimensions(): void
    {
        foreach ($this->tuple as $dimension) {
            $key = $dimension->getKey();

            if ($key === '@values') {
                // Skip the @values key, it is not a dimension
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $rawMember = $dimension->getRawMember();

            $this->processDimension($key, $rawMember);
        }
    }

    private function processDimension(string $key, mixed $rawMember): void
    {
        $dimensionMetadata = $this->summaryMetadata->getProperty($key);

        if ($dimensionMetadata instanceof DimensionMetadata) {
            $this->processStandaloneDimension($dimensionMetadata, $rawMember);
        } elseif ($dimensionMetadata instanceof DimensionPropertyMetadata) {
            $this->processDimensionProperty($dimensionMetadata, $rawMember);
        } else {
            throw new UnexpectedValueException(
                \sprintf('Invalid dimension metadata for key "%s"', $key),
            );
        }
    }

    private function processStandaloneDimension(
        DimensionMetadata $dimension,
        mixed $rawMember,
    ): void {
        $valueResolvers = $dimension->getSource();

        if (\count($valueResolvers) !== 1) {
            throw new QueryException('More than one ValueResolver is not supported');
        }

        $valueResolver = reset($valueResolvers);

        $dql = $valueResolver->getDQL(
            context: new Context(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->summaryMetadata,
                dimensionMetadata: $dimension,
            ),
        );

        $this->getSimpleQueryBuilder()->andWhere(\sprintf(
            '%s = %s',
            $dql,
            $this->createNamedParameter($rawMember),
        ));
    }

    private function processDimensionProperty(
        DimensionPropertyMetadata $dimensionProperty,
        mixed $rawMember,
    ): void {
        $dimension = $dimensionProperty->getDimension();

        // get the value resolver from dimension

        $valueResolvers = $dimension->getSource();

        if (\count($valueResolvers) !== 1) {
            throw new QueryException('More than one ValueResolver is not supported');
        }

        $valueResolver = reset($valueResolvers);

        // get the value resolver from hierarchical dimension

        $hierarchicalDimensionValueResolver = $dimensionProperty->getValueResolver();

        $dql = $hierarchicalDimensionValueResolver->getDQL(
            input: $valueResolver,
            context: new Context(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->summaryMetadata,
                dimensionMetadata: $dimension,
                dimensionPropertyMetadata: $dimensionProperty->getDimensionLevelProperty(),
            ),
        );

        // add to query

        $this->getSimpleQueryBuilder()->andWhere(\sprintf(
            '%s = %s',
            $dql,
            $this->createNamedParameter($rawMember),
        ));
    }

    private function processOrderBy(): void
    {
        $identifier = $this->entityManager
            ->getClassMetadata($this->summaryMetadata->getSourceClasses()[0])
            ->getSingleIdentifierFieldName();

        $this->getSimpleQueryBuilder()->orderBy(
            'root.' . $identifier,
            'ASC',
        );
    }

    private function createNamedParameter(mixed $value): string
    {
        if (\is_object($value) && $this->entityManager->contains($value)) {
            // If the value is an entity, we use its identifier, because we do
            // the same thing in reverse
            /** @psalm-suppress MixedAssignment */
            $value = $this->entityManager
                ->getUnitOfWork()
                ->getSingleIdentifierValue($value);
        }

        return $this->getSimpleQueryBuilder()->createNamedParameter($value);
    }
}
