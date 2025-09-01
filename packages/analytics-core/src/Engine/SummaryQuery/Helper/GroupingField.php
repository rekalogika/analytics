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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Helper;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

final readonly class GroupingField
{
    /**
     * @var list<string>
     */
    private array $groupingFields;

    /**
     * @var list<string>
     */
    private array $nonGroupingFields;

    /**
     * @param list<string> $dimensions
     */
    public function __construct(
        mixed $groupingField,
        array $dimensions,
    ) {
        if (!\is_string($groupingField) && !\is_int($groupingField)) {
            throw new InvalidArgumentException('Grouping field must be a string or an integer.');
        }

        $groupingField = (string) $groupingField;
        $isGrouping = array_map(fn($char) => $char === '1', str_split($groupingField));

        $dimensions = array_filter(
            $dimensions,
            static fn(string $dimension) => $dimension !== '@values',
        );

        $i = 0;

        $groupingFields = [];
        $nonGroupingFields = [];

        foreach ($dimensions as $dimension) {
            $dimensionIsGrouping = $isGrouping[$i]
                ?? throw new InvalidArgumentException(\sprintf(
                    'Grouping field "%s" has less dimensions than the group by fields: %s',
                    $groupingField,
                    implode(', ', $dimensions),
                ));

            if ($dimensionIsGrouping) {
                $groupingFields[] = $dimension;
            } else {
                $nonGroupingFields[] = $dimension;
            }

            ++$i;
        }

        $this->groupingFields = $groupingFields;
        $this->nonGroupingFields = $nonGroupingFields;

        if ($i !== \count($isGrouping)) {
            throw new InvalidArgumentException(\sprintf(
                'Grouping field "%s" has different dimension count than the group by fields: %s',
                $groupingField,
                implode(', ', $dimensions),
            ));
        }
    }

    /**
     * @return list<string>
     */
    public function getNonGroupingFields(): array
    {
        return $this->nonGroupingFields;
    }

    /**
     * @return list<string>
     */
    public function getGroupingFields(): array
    {
        return $this->groupingFields;
    }

    public function hasOneNonGroupingField(): bool
    {
        return \count($this->nonGroupingFields) === 1;
    }
}
