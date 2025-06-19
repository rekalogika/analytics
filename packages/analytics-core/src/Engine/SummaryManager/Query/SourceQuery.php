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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Result\Tuple;
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
        $sourceClass = $summaryMetadata->getSourceClass();

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
            $name = $dimension->getName();

            if ($name === '@values') {
                // Skip the @values key, it is not a dimension
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $rawMember = $dimension->getRawMember();

            $this->processDimension($name, $rawMember);
        }
    }

    private function processDimension(string $name, mixed $rawMember): void
    {
        $dimensionMetadata = $this->summaryMetadata->getProperty($name);

        if ($dimensionMetadata instanceof DimensionMetadata) {
            $this->processStandaloneDimension($dimensionMetadata, $rawMember);
        } elseif ($dimensionMetadata instanceof DimensionPropertyMetadata) {
            $this->processDimensionProperty($dimensionMetadata, $rawMember);
        } else {
            throw new UnexpectedValueException(
                \sprintf('Invalid dimension metadata for key "%s"', $name),
            );
        }
    }

    private function processStandaloneDimension(
        DimensionMetadata $dimension,
        mixed $rawMember,
    ): void {
        $valueResolver = $dimension->getValueResolver();

        $expression = $valueResolver->getExpression(
            context: new SourceQueryContext(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->summaryMetadata,
                dimensionMetadata: $dimension,
            ),
        );

        $this->getSimpleQueryBuilder()->andWhere(\sprintf(
            '%s = %s',
            $expression,
            $this->createNamedParameter($rawMember),
        ));
    }

    private function processDimensionProperty(
        DimensionPropertyMetadata $dimensionProperty,
        mixed $rawMember,
    ): void {
        $valueResolver = $dimensionProperty->getValueResolver();

        $expression = $valueResolver->getExpression(
            context: new SourceQueryContext(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->summaryMetadata,
                dimensionMetadata: $dimensionProperty->getDimension(),
                dimensionPropertyMetadata: $dimensionProperty,
            ),
        );

        // add to query

        $this->getSimpleQueryBuilder()->andWhere(\sprintf(
            '%s = %s',
            $expression,
            $this->createNamedParameter($rawMember),
        ));
    }

    private function processOrderBy(): void
    {
        $identifier = $this->entityManager
            ->getClassMetadata($this->summaryMetadata->getSourceClass())
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
