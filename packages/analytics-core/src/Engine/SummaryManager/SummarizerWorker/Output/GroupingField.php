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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;

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
        if (!\is_string($groupingField)) {
            throw new InvalidArgumentException('Grouping field must be a string or an integer.');
        }

        $dimensions = array_filter(
            $dimensions,
            static fn(string $dimension) => $dimension !== '@values',
        );

        if (\strlen($groupingField) !== \count($dimensions)) {
            throw new InvalidArgumentException(\sprintf(
                'Grouping field "%s" must have the same number of dimensions as the group by fields: %s',
                $groupingField,
                implode(', ', $dimensions),
            ));
        }

        $groupingsCount = substr_count($groupingField, '1');
        $nonGroupingCount = substr_count($groupingField, '0');

        if ($groupingsCount + $nonGroupingCount !== \strlen($groupingField)) {
            throw new InvalidArgumentException('Grouping field must only contain 0 and 1 characters.');
        }

        // nonGroupingFields are the $nonGroupingCount fields from the start of $dimensions
        $this->nonGroupingFields = \array_slice(
            $dimensions,
            0,
            $nonGroupingCount,
        );

        // groupingFields are the $groupingsCount fields from the end of $dimensions
        if ($groupingsCount === 0) {
            $this->groupingFields = [];
        } else {
            $this->groupingFields = \array_slice(
                $dimensions,
                -$groupingsCount,
            );
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

    public function isSubtotal(): bool
    {
        return $this->getGroupingFields() !== [];
    }
}
