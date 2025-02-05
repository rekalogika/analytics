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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker;

use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Query\Implementation\DefaultSummaryNode;
use Symfony\Component\Translation\TranslatableMessage;

final readonly class ResultToDimensionTableTransformer
{
    public function __construct(
        private SummaryMetadata $metadata,
    ) {}

    /**
     * @param iterable<array<string,array{mixed,mixed}>> $input
     * @return iterable<list<DefaultSummaryNode>>
     */
    public function transformResultToDimensionTable(
        iterable $input,
    ): iterable {
        $previousRow = [];
        $previousTransformedRow = [];
        $valuesMessage = new TranslatableMessage('Values');

        foreach ($input as $row) {
            $transformedRow = [];
            $lastKey = null;

            foreach ($row as $key => $v) {
                /**
                 * @var mixed $rawValue
                 * @var mixed $value
                 */
                [$rawValue, $value] = $v;

                // if the current value is the same as the above cell, then use
                // the above cell's result

                if (
                    \array_key_exists($key, $previousRow)
                    && ($previousRow[$key]) === $value
                    && $key !== '@measure'
                ) {
                    $transformedRow[$key] = $previousTransformedRow[$key];
                    $lastKey = $key;

                    continue;
                }

                if (str_contains($key, '.')) {
                    [$dimensionName, $propertyName] = explode('.', $key);
                    $metadata = $this->metadata->getDimensionMetadata($dimensionName);

                    $hierarchyMetadata = $metadata->getHierarchy();

                    if ($hierarchyMetadata === null) {
                        throw new \RuntimeException(\sprintf('Hierarchy not found: %s', $dimensionName));
                    }

                    $name = new TranslatableMessage(
                        '{property} - {dimension}',
                        [
                            '{property}' => $metadata->getLabel(),
                            '{dimension}' => $hierarchyMetadata
                                ->getProperty($propertyName)
                                ->getLabel(),
                        ],
                    );

                    $transformedRow[$key] = DefaultSummaryNode::createBranchItem(
                        key: $key,
                        item: $value,
                        legend: $name,
                    );
                } elseif ($key === '@values') {
                    $name = $valuesMessage;

                    $transformedRow[$key] = DefaultSummaryNode::createBranchItem(
                        key: $key,
                        item: $value,
                        legend: $name,
                    );
                } elseif ($key === '@measure') {
                    $lastItem = $transformedRow[$lastKey] ?? null;

                    if (
                        $lastItem instanceof DefaultSummaryNode
                    ) {
                        if (
                            !\is_int($rawValue)
                            && !\is_float($rawValue)
                            && $rawValue !== null
                        ) {
                            throw new \RuntimeException(\sprintf('Invalid value: %s', get_debug_type($rawValue)));
                        }

                        \assert(\is_string($lastKey));

                        $transformedRow[$lastKey] = DefaultSummaryNode::createLeafItem(
                            key: $lastItem->getKey(),
                            item: $lastItem->getItem(),
                            value: $value,
                            rawValue: $rawValue,
                            legend: $lastItem->getLegend(),
                        );
                    }
                } else {
                    $metadata = $this->metadata->getDimensionMetadata($key);
                    $name = $metadata->getLabel();

                    $transformedRow[$key] = DefaultSummaryNode::createBranchItem(
                        key: $key,
                        item: $value,
                        legend: $name,
                    );
                }

                $lastKey = $key;
            }

            /** @var array<array-key,DefaultSummaryNode> $transformedRow */

            yield array_values($transformedRow);

            $previousRow = $row;
            $previousTransformedRow = $transformedRow;
        }
    }
}
