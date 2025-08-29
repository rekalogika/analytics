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

namespace Rekalogika\Analytics\Engine\SourceEntities\Query;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\ExpressionUtil;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\SourceExpressionVisitor;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final class SourceQuery extends AbstractQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $summaryMetadata,
    ) {
        $sourceClass = $summaryMetadata->getSourceClass();

        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $sourceClass,
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $this->addQueryBuilderModifier();
    }

    public function selectRoot(): self
    {
        $this->getSimpleQueryBuilder()->select('root');
        $this->addOrderByIdentifier();

        return $this;
    }

    public function selectMeasures(): self
    {
        $this->addAllMeasuresToSelect();

        return $this;
    }

    public function fromCoordinates(Coordinates $coordinates): self
    {
        $this->addCoordinatesDimensionsToWhere($coordinates);

        $predicate = $coordinates->getPredicate();

        if ($predicate !== null) {
            $this->addExpressionsToWhere($predicate);
        }

        return $this;
    }

    public function fromQuery(Query $query): self
    {
        $this->addQueryDimensionsToSelectGroupByOrderBy($query);

        $expressions = $query->getDice();

        if ($expressions !== null) {
            $this->addExpressionsToWhere($expressions);
        }

        return $this;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getSimpleQueryBuilder()->getQueryBuilder();
    }

    //
    // private methods
    //

    /**
     * @todo allow virtual measures
     * @todo fix hll
     */
    private function addAllMeasuresToSelect(): void
    {
        foreach ($this->summaryMetadata->getMeasures() as $measureMetadata) {
            $function = $measureMetadata->getFunction();

            if (!$function instanceof SummarizableAggregateFunction) {
                continue;
            }

            $expression = $function->getSourceToAggregateExpression(
                context: new SourceQueryContext(
                    queryBuilder: $this->getSimpleQueryBuilder(),
                    summaryMetadata: $this->summaryMetadata,
                    measureMetadata: $measureMetadata,
                ),
            );

            $this->getSimpleQueryBuilder()->addSelect($expression);
        }
    }

    private function addCoordinatesDimensionsToWhere(Coordinates $coordinates): void
    {
        foreach ($coordinates as $dimension) {
            $name = $dimension->getName();

            if ($name === '@values') {
                // Skip the @values key, it is not a dimension
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $rawMember = $dimension->getRawMember();

            $this->addDimensionToWhere($name, $rawMember);
        }
    }

    private function addDimensionToWhere(string $name, mixed $rawMember): void
    {
        $dimensionMetadata = $this->summaryMetadata->getDimension($name);
        $valueResolver = $dimensionMetadata->getValueResolver();

        $expression = $valueResolver->getExpression(
            context: new SourceQueryContext(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->summaryMetadata,
                dimensionMetadata: $dimensionMetadata,
            ),
        );

        if ($rawMember === null) {
            $this->getSimpleQueryBuilder()->andWhere(\sprintf(
                'REKALOGIKA_IS_NULL(%s) = TRUE',
                $expression,
            ));
        } else {
            $this->getSimpleQueryBuilder()->andWhere(\sprintf(
                '%s = %s',
                $expression,
                $this->createNamedParameter($rawMember),
            ));
        }
    }

    private function addQueryDimensionsToSelectGroupByOrderBy(Query $query): void
    {
        $dimensions = $query->getDimensions();

        foreach ($dimensions as $dimension) {
            $dimensionMetadata = $this->summaryMetadata->getDimension($dimension);
            $alias = $dimensionMetadata->getDqlAlias();
            $valueResolver = $dimensionMetadata->getValueResolver();

            $expression = $valueResolver->getExpression(
                context: new SourceQueryContext(
                    queryBuilder: $this->getSimpleQueryBuilder(),
                    summaryMetadata: $this->summaryMetadata,
                    dimensionMetadata: $dimensionMetadata,
                ),
            );

            $this->getSimpleQueryBuilder()->addSelect(\sprintf(
                '%s AS %s',
                $expression,
                $alias,
            ));

            $this->getSimpleQueryBuilder()->addGroupBy($alias);
            $this->getSimpleQueryBuilder()->addOrderBy($alias, 'ASC');
        }
    }

    private function addQueryBuilderModifier(): void
    {
        $class = $this->summaryMetadata->getSummaryClass();

        if (is_a($class, HasQueryBuilderModifier::class, true)) {
            $class::modifyQueryBuilder(
                $this->getSimpleQueryBuilder()->getQueryBuilder(),
            );
        }
    }

    private function addExpressionsToWhere(Expression $expression): void
    {
        ExpressionUtil::addExpressionToQueryBuilder(
            metadata: $this->summaryMetadata,
            queryBuilder: $this->getSimpleQueryBuilder(),
            expression: $expression,
            visitorClass: SourceExpressionVisitor::class,
        );
    }

    private function addOrderByIdentifier(): void
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
