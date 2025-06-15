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

use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Core\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final readonly class SummaryQueryContext
{
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
     * @param list<string> $calledMeasures
     */
    private function __construct(
        private SimpleQueryBuilder $queryBuilder,
        private SummaryMetadata $summaryMetadata,
        private MeasureMetadata $measureMetadata,
        private array $calledMeasures,
    ) {}

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
            ->resolve($measureMetadata->getSummaryProperty());

        $function = $measureMetadata->getFirstFunction();

        if ($function instanceof SummarizableAggregateFunction) {
            $aggregateToAggregateExpression =
                $function->getAggregateToAggregateExpression($field);
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

    public function getMeasureMetadata(): MeasureMetadata
    {
        return $this->measureMetadata;
    }
}
