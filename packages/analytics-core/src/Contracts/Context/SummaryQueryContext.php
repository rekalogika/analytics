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

namespace Rekalogika\Analytics\Contracts\Context;

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Summary\PseudoMeasure;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

/**
 * Context given to `AggregateFunction::getAggregateToResultExpression()` while
 * the framework builds the DQL query that reads the summary table. It lets an
 * aggregate function reference the result expression of another measure of
 * the same summary, so a measure can be derived from other measures, e.g. an
 * average computed as a sum divided by a count.
 *
 * @see \Rekalogika\Analytics\Contracts\Summary\AggregateFunction
 */
final readonly class SummaryQueryContext
{
    /**
     * Creates the root context for a measure. Called by the framework.
     */
    public static function create(
        SimpleQueryBuilder $queryBuilder,
        SummaryMetadata $summaryMetadata,
        MeasureMetadata $measureMetadata,
    ): self {
        return new self(
            queryBuilder: $queryBuilder,
            summaryMetadata: $summaryMetadata,
            measureMetadata: $measureMetadata,
            calledMeasures: [],
        );
    }

    /**
     * @param list<string> $calledMeasures The measures already being resolved
     * up the call chain, used to detect loops.
     */
    private function __construct(
        private SimpleQueryBuilder $queryBuilder,
        private SummaryMetadata $summaryMetadata,
        private MeasureMetadata $measureMetadata,
        private array $calledMeasures,
    ) {}

    /**
     * Returns the DQL expression that produces the final value of another
     * measure of the same summary. The expression combines the measure's
     * aggregate-to-aggregate expression (if it is a
     * `SummarizableAggregateFunction`) with its aggregate-to-result
     * expression, so the result is the same value the measure would have in
     * the query result.
     *
     * @param string $property The name of the measure in the summary class.
     * @throws UnexpectedValueException If the measure is already being
     * resolved up the call chain (a loop), or if it is a `PseudoMeasure`,
     * which has no DQL representation.
     */
    public function resolve(string $property): string
    {
        if (\in_array($property, $this->calledMeasures, true)) {
            throw new UnexpectedValueException(\sprintf(
                'Loop detected, measure "%s" is already called in this context.',
                $property,
            ));
        }

        $measureMetadata = $this->summaryMetadata->getMeasure($property);

        // create summary to summary DQL expression

        $field = $this->queryBuilder
            ->resolve($measureMetadata->getName());

        $function = $measureMetadata->getFunction();

        if ($function instanceof SummarizableAggregateFunction) {
            $aggregateToAggregateExpression =
                $function->getAggregateToAggregateExpression($field);
        } elseif ($function instanceof PseudoMeasure) {
            throw new UnexpectedValueException(\sprintf(
                'The special measure "%s" cannot be resolved in this context.',
                $property,
            ));
        } else {
            $aggregateToAggregateExpression = '';
        }

        // create context

        $context = new SummaryQueryContext(
            queryBuilder: $this->queryBuilder,
            summaryMetadata: $this->summaryMetadata,
            calledMeasures: [...$this->calledMeasures, $property],
            measureMetadata: $measureMetadata,
        );

        // create summary to result DQL expression

        $aggregateToResultExpression = $function->getAggregateToResultExpression(
            inputExpression: $aggregateToAggregateExpression,
            context: $context,
        );

        return $aggregateToResultExpression;
    }

    /**
     * The metadata of the measure whose expression is being built.
     */
    public function getMeasureMetadata(): MeasureMetadata
    {
        return $this->measureMetadata;
    }
}
