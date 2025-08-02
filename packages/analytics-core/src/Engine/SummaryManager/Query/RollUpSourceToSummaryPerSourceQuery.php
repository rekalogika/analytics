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
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Engine\SummaryManager\Groupings\Groupings;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\PartitionHandler;
use Rekalogika\Analytics\Engine\SummaryManager\ValueResolver\Bust;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\DoctrineAdvancedGroupBy\Field;

final class RollUpSourceToSummaryPerSourceQuery extends AbstractQuery
{
    private ?Groupings $groupings = null;
    private ?Partition $start = null;
    private ?Partition $end = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly PartitionHandler $partitionManager,
        private readonly SummaryMetadata $summaryMetadata,
    ) {
        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $this->summaryMetadata->getSourceClass(),
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);
    }

    private function getGroupings(): Groupings
    {
        return $this->groupings ??= Groupings::create($this->summaryMetadata);
    }

    public function withBoundary(Partition $start, Partition $end): self
    {
        if ($this->start !== null || $this->end !== null) {
            throw new UnexpectedValueException('Boundary has already been set.');
        }

        $clone = clone $this;
        $clone->start = $start;
        $clone->end = $end;

        return $clone;
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    public function getQueries(): iterable
    {
        $this->initialize();
        $this->processPartition();
        $this->processDimensions();
        $this->processMeasures();
        $this->processBoundary();
        $this->processGroupings();
        $this->processQueryBuilderModifier();

        yield $this->createQuery();
    }

    private function initialize(): void
    {
        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                "REKALOGIKA_NEXTVAL(%s)",
                $this->summaryMetadata->getSummaryClass(),
            ));
    }

    private function processPartition(): void
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $valueResolver = $partitionMetadata->getSource();
        $partitionClass = $partitionMetadata->getPartitionClass();
        $partitioningLevels = $partitionClass::getAllLevels();
        $lowestLevel = min($partitioningLevels);

        $function = $partitionClass::getClassifierExpression(
            input: $valueResolver,
            level: $lowestLevel,
            context: new SourceQueryContext(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->summaryMetadata,
                partitionMetadata: $partitionMetadata,
            ),
        );

        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                '%s AS par_key',
                $function,
            ))
            ->addSelect(\sprintf(
                '%s AS par_level',
                $lowestLevel,
            ));
    }

    /**
     * @see SourceExpressionVisitor::visitField()
     */
    private function processDimensions(): void
    {
        foreach ($this->summaryMetadata->getLeafDimensions() as $dimensionMetadata) {
            $valueResolver = $dimensionMetadata->getValueResolver();
            $valueResolver = Bust::create($valueResolver);

            $expression = $valueResolver->getExpression(
                context: new SourceQueryContext(
                    queryBuilder: $this->getSimpleQueryBuilder(),
                    summaryMetadata: $this->summaryMetadata,
                    dimensionMetadata: $dimensionMetadata,
                ),
            );

            $alias = $dimensionMetadata->getDqlAlias();

            $this->getSimpleQueryBuilder()
                ->addSelect(\sprintf('%s AS %s', $expression, $alias));

            $this->getGroupings()->registerExpression(
                name: $dimensionMetadata->getName(),
                expression: $expression,
            );
        }
    }

    private function processMeasures(): void
    {
        foreach ($this->summaryMetadata->getMeasures() as $measureMetadata) {
            if ($measureMetadata->isVirtual()) {
                continue;
            }

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

    private function processBoundary(): void
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $valueResolver = $partitionMetadata->getSource();
        $properties = $valueResolver->getInvolvedProperties();

        if (\count($properties) !== 1) {
            throw new UnexpectedValueException(\sprintf(
                'Expected exactly one property, got %d',
                \count($properties),
            ));
        }

        $property = $properties[0];

        $this->getSimpleQueryBuilder()
            ->andWhere(\sprintf(
                "%s >= :startBoundary",
                $this->resolve($property),
            ))
            ->andWhere(\sprintf(
                "%s < :endBoundary",
                $this->resolve($property),
            ));

        if ($this->start === null || $this->end === null) {
            $this->getSimpleQueryBuilder()
                ->setParameter('startBoundary', '(placeholder) the start boundary')
                ->setParameter('endBoundary', '(placeholder) the end boundary');

            return;
        }

        /** @psalm-suppress MixedAssignment */
        $start = $this->partitionManager
            ->getLowerBoundSourceValueFromPartition($this->start);

        /** @psalm-suppress MixedAssignment */
        $end = $this->partitionManager
            ->getUpperBoundSourceValueFromPartition($this->end);

        $this->getSimpleQueryBuilder()
            ->setParameter('startBoundary', $start)
            ->setParameter('endBoundary', $end);
    }

    private function processQueryBuilderModifier(): void
    {
        $class = $this->summaryMetadata->getSummaryClass();

        if (is_a($class, HasQueryBuilderModifier::class, true)) {
            $class::modifyQueryBuilder(
                $this->getSimpleQueryBuilder()->getQueryBuilder(),
            );
        }
    }

    private function processGroupings(): void
    {
        $this->getSimpleQueryBuilder()
            ->addSelect($this->getGroupings()->getExpression());
    }

    private function createQuery(): DecomposedQuery
    {
        $query = $this->getSimpleQueryBuilder()->getQuery();

        $groupBy = $this->summaryMetadata->getGroupByExpression();
        $groupBy->add(new Field('par_key'));
        $groupBy->add(new Field('par_level'));
        $groupBy->apply($query);

        return DecomposedQuery::createFromQuery($query);
    }
}
