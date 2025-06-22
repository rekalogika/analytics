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
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Engine\SummaryManager\Groupings\Groupings;
use Rekalogika\Analytics\Engine\SummaryManager\PartitionManager\PartitionManager;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;

final class RollUpSourceToSummaryPerSourceQuery extends AbstractQuery
{
    private Groupings $groupings;

    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly PartitionManager $partitionManager,
        private readonly SummaryMetadata $summaryMetadata,
        private readonly Partition $start,
        private readonly Partition $end,
    ) {
        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $this->summaryMetadata->getSourceClass(),
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $this->groupings = Groupings::create($summaryMetadata);
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    public function getQuery(): iterable
    {
        $this->initialize();
        $this->processPartition();
        $this->processDimensions();
        $this->processMeasures();
        $this->processConstraints();
        $this->processGroupings();
        $this->processQueryBuilderModifier();

        yield $this->createSqlStatement();
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

    private function processDimensions(): void
    {
        foreach ($this->summaryMetadata->getLeafDimensions() as $dimensionMetadata) {
            $valueResolver = $dimensionMetadata->getValueResolver();

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

            $this->groupings->registerExpression(
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

    private function processConstraints(): void
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $valueResolver = $partitionMetadata->getSource();

        /** @psalm-suppress MixedAssignment */
        $start = $this->partitionManager
            ->calculateSourceBoundValueFromPartition($this->start, 'lower');

        /** @psalm-suppress MixedAssignment */
        $end = $this->partitionManager
            ->calculateSourceBoundValueFromPartition($this->end, 'upper');

        // add constraints

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
                "%s >= %s",
                $this->resolve($property),
                $this->getSimpleQueryBuilder()->createNamedParameter($start),
            ))
            ->andWhere(\sprintf(
                "%s < %s",
                $this->resolve($property),
                $this->getSimpleQueryBuilder()->createNamedParameter($end),
            ));
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
            ->addSelect($this->groupings->getExpression());
    }

    private function createSqlStatement(): DecomposedQuery
    {
        $query = $this->getSimpleQueryBuilder()->getQuery();

        $groupBy = new GroupBy();
        $groupBy->add(new Field('par_key'));
        $groupBy->add(new Field('par_level'));

        foreach ($this->summaryMetadata->getGroupByExpression() as $groupByExpression) {
            $groupBy->add($groupByExpression);
        }

        $groupBy->apply($query);

        return DecomposedQuery::createFromQuery($query);
    }
}
