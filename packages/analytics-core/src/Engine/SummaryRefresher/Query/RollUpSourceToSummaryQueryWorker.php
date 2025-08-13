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

namespace Rekalogika\Analytics\Engine\SummaryRefresher\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Core\ValueResolver\NullValue;
use Rekalogika\Analytics\Engine\Groupings\Groupings;
use Rekalogika\Analytics\Engine\Handler\PartitionHandler;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\Util\CollectAliasesInGroupByVisitor;
use Rekalogika\Analytics\Engine\SummaryRefresher\ValueResolver\Bust;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;

final class RollUpSourceToSummaryQueryWorker extends AbstractQuery implements SummaryRefresherQuery
{
    private Groupings $groupings;

    /**
     * @var array<string,true>
     */
    private array $fieldsInGroupBy = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly PartitionHandler $partitionManager,
        private readonly SummaryMetadata $summaryMetadata,
        private readonly string $insertSql,
        private readonly GroupBy $groupBy,
    ) {
        // query builder
        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $this->summaryMetadata->getSourceClass(),
            alias: 'root',
        );

        // groupings
        $this->groupings = Groupings::create($this->summaryMetadata);

        parent::__construct($simpleQueryBuilder);

        $this->initialize();
        $this->processFieldsInGroupBy();
        $this->processPartition(); // select
        $this->processDimensions(); // select
        $this->processMeasures(); // select
        $this->processBoundary(); // where
        $this->processGroupings(); // select
        $this->processQueryBuilderModifier(); // user defined
    }

    private function getGroupings(): Groupings
    {
        return $this->groupings;
    }

    #[\Override]
    public function withBoundary(Partition $start, Partition $end): static
    {
        $clone = clone $this;

        /** @psalm-suppress MixedAssignment */
        $start = $clone->partitionManager
            ->getLowerBoundSourceValueFromPartition($start);

        /** @psalm-suppress MixedAssignment */
        $end = $clone->partitionManager
            ->getUpperBoundSourceValueFromPartition($end);

        $clone->getSimpleQueryBuilder()
            ->setParameter('startBoundary', $start)
            ->setParameter('endBoundary', $end);

        return $clone;
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    #[\Override]
    public function getQueries(): iterable
    {
        $query = $this->getSimpleQueryBuilder()->getQuery();

        $groupBy = $this->groupBy;
        $groupBy->add(new Field('par_key'));
        $groupBy->add(new Field('par_level'));
        $groupBy->apply($query);

        yield DecomposedQuery::createFromQuery($query)
            ->prependSql($this->insertSql);
    }

    private function initialize(): void
    {
        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                "REKALOGIKA_NEXTVAL(%s)",
                $this->summaryMetadata->getSummaryClass(),
            ));
    }

    private function processFieldsInGroupBy(): void
    {
        $visitor = new CollectAliasesInGroupByVisitor();
        $this->groupBy->accept($visitor);
        $aliasesInGroupBy = $visitor->getAliases();

        foreach ($aliasesInGroupBy as $alias) {
            $dimensionMetadata = $this->summaryMetadata->getDimensionByAlias($alias);
            $name = $dimensionMetadata->getName();

            $this->fieldsInGroupBy[$name] = true;
            $this->getGroupings()->registerFieldInGroupBy($name);
        }
    }

    private function processPartition(): void
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $valueResolver = $partitionMetadata->getSource();
        $partitionClass = $partitionMetadata->getPartitionClass();
        /** @psalm-suppress ImpureMethodCall */
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
            // if dimension is in group by
            if ($this->fieldsInGroupBy[$dimensionMetadata->getName()] ?? false) {
                $valueResolver = $dimensionMetadata->getValueResolver();

                /** @psalm-suppress ImpureMethodCall */
                $valueResolver = Bust::create($valueResolver);
            } else {
                $valueResolver = new NullValue();
            }

            $expression = $valueResolver->getExpression(
                context: new SourceQueryContext(
                    queryBuilder: $this->getSimpleQueryBuilder(),
                    summaryMetadata: $this->summaryMetadata,
                    dimensionMetadata: $dimensionMetadata,
                ),
            );

            $alias = $dimensionMetadata->getDqlAlias();

            $this->getGroupings()->registerExpression(
                name: $dimensionMetadata->getName(),
                expression: $expression,
            );

            $this->getSimpleQueryBuilder()
                ->addSelect(\sprintf('%s AS %s', $expression, $alias));
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

        $this->getSimpleQueryBuilder()
            ->setParameter('startBoundary', '(placeholder) the start boundary')
            ->setParameter('endBoundary', '(placeholder) the end boundary');
    }

    private function processQueryBuilderModifier(): void
    {
        $class = $this->summaryMetadata->getSummaryClass();

        if (is_a($class, HasQueryBuilderModifier::class, true)) {
            /** @psalm-suppress ImpureMethodCall */
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
}
