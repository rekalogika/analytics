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

namespace Rekalogika\Analytics\Contracts\Summary;

use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final readonly class SummaryContext
{
    public static function create(
        SimpleQueryBuilder $queryBuilder,
        SummaryMetadata $summaryMetadata,
    ): self {
        return new self(
            queryBuilder: $queryBuilder,
            summaryMetadata: $summaryMetadata,
            calledMeasures: [],
        );
    }

    /**
     * @param list<string> $calledMeasures
     */
    private function __construct(
        private SimpleQueryBuilder $queryBuilder,
        private SummaryMetadata $summaryMetadata,
        private array $calledMeasures,
    ) {}

    public function getMeasureDQL(string $measure): string
    {
        if (\in_array($measure, $this->calledMeasures, true)) {
            throw new UnexpectedValueException(\sprintf(
                'Loop detected, measure "%s" is already called in this context.',
                $measure,
            ));
        }

        $measureMetadata = $this->summaryMetadata->getMeasure($measure);
        $function = $measureMetadata->getFirstFunction();

        // create summary to summary DQL

        $field = $this->queryBuilder
            ->resolve($measureMetadata->getSummaryProperty());

        $aggregateToAggregateDQLExpression =
            $function->getAggregateToAggregateDQLExpression($field);

        // create context

        $context = new SummaryContext(
            queryBuilder: $this->queryBuilder,
            summaryMetadata: $this->summaryMetadata,
            calledMeasures: [...$this->calledMeasures, $measure],
        );

        // create summary to result DQL

        $aggregateToResultDQLExpression = $function->getAggregateToResultDQLExpression(
            inputExpression: $aggregateToAggregateDQLExpression ?? '',
            context: $context,
        );

        return $aggregateToResultDQLExpression;
    }
}
