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

namespace Rekalogika\Analytics\Engine\Groupings;

use Rekalogika\Analytics\Contracts\Exception\GroupingSetNotAvailable;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final class Groupings
{
    /**
     * @var array<string,string>
     */
    private array $nameToExpression = [];

    /**
     * @var array<string,true>
     */
    private array $selected = [];

    /**
     * @var array<string,true>
     */
    private array $fieldsInGroupBy = [];

    /**
     * @var list<string>
     */
    private readonly array $groupingFields;

    private function __construct(
        private readonly SummaryMetadata $summaryMetadata,
    ) {
        $this->groupingFields = array_keys($summaryMetadata->getLeafDimensions());
    }

    public static function create(SummaryMetadata $summaryMetadata): self
    {
        return new self($summaryMetadata);
    }

    //
    // for rollups
    //

    public function registerFieldInGroupBy(string $name): void
    {
        $this->fieldsInGroupBy[$name] = true;
    }

    public function isInGroupBy(string $name): bool
    {
        return $this->fieldsInGroupBy[$name] ?? false;
    }

    public function registerExpression(string $name, string $expression): void
    {
        if (
            $expression !== 'REKALOGIKA_NULL()' // exclude null expression
            && \in_array($expression, $this->nameToExpression, true)
        ) {
            $previousProperty = array_search($expression, $this->nameToExpression, true);

            if ($previousProperty === false) {
                throw new LogicException("Should never happen");
            }

            throw new InvalidArgumentException(\sprintf(
                'Expression "%s" already exists for property "%s", and you are trying to add the same expression for property "%s". Two properties with the same expression in the same summary class is not allowed because it will confuse the database.',
                $expression,
                $name,
                $previousProperty,
            ));
        }

        $this->nameToExpression[$name] = $expression;
    }

    public function getExpression(): string
    {
        $grouping = [];

        foreach ($this->groupingFields as $groupingField) {
            if ($this->isInGroupBy($groupingField)) {
                $expression = $this->nameToExpression[$groupingField]
                    ?? throw new LogicException(\sprintf(
                        'Grouping field "%s" is not registered in the groupings. Make sure to register the expression for the grouping field before calling getExpression().',
                        $groupingField,
                    ));
            } else {
                $expression = "'__GROUPING__'";
            }

            $grouping[$groupingField] = $expression;
        }

        ksort($grouping);

        return 'REKALOGIKA_GROUPING_CONCAT(' . implode(', ', $grouping) . ')';
    }

    //
    // for queries
    //

    public function addSelected(string $name): void
    {
        $this->selected[$name] = true;
    }

    /**
     * @return list<string> The names of the selected grouping fields.
     */
    private function getSelected(): array
    {
        return array_keys($this->selected);
    }

    public function getGroupingStringForSelect(): string
    {
        $selector = new GroupingSetSelector(
            $this->summaryMetadata->getGroupByExpression(),
        );

        $selected = $this->getSelected();

        $aliases = array_map(
            fn(string $name): string => $this->summaryMetadata
                ->getDimension($name)
                ->getDqlAlias(),
            $selected,
        );

        $result = $selector->select($aliases);

        if ($result === null) {
            throw $this->createGroupingSetNotAvailableException(
                selector: $selector,
                selected: $selected,
            );
        }

        $groupingFields = [];

        foreach ($this->groupingFields as $groupingField) {
            $groupingFields[$groupingField] = '1';
        }

        foreach ($result as $alias) {
            $dimension = $this->summaryMetadata->getDimensionByAlias($alias);
            $groupingFields[$dimension->getName()] = '0';
        }

        ksort($groupingFields);

        return implode('', $groupingFields);
    }

    /**
     * @param list<string> $selected
     */
    private function createGroupingSetNotAvailableException(
        GroupingSetSelector $selector,
        array $selected,
    ): GroupingSetNotAvailable {
        $labels = array_map(
            fn(string $name): TranslatableInterface => $this->summaryMetadata
                ->getDimension($name)
                ->getLabel(),
            $selected,
        );

        $availableSets = array_map(
            fn(array $aliases): array => array_map(
                fn(string $alias): string => $this->summaryMetadata
                    ->getDimensionByAlias($alias)
                    ->getName(),
                $aliases,
            ),
            $selector->getSets(20),
        );

        return new GroupingSetNotAvailable(
            summaryClass: $this->summaryMetadata->getSummaryClass(),
            dimensions: $selected,
            dimensionLabels: $labels,
            availableSets: $availableSets,
            availableSetsCount: $selector->count(),
        );
    }
}
