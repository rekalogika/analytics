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

namespace Rekalogika\Analytics\Engine\SummaryManager\Groupings;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

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
     * @param array<string,string> $groupingFieldToName
     * @param array<string,string> $nameToGroupingField
     */
    private function __construct(
        private readonly array $groupingFieldToName,
        private array $nameToGroupingField,
    ) {}

    public static function create(SummaryMetadata $summaryMetadata): self
    {
        $groupingFieldToDimension = [];
        $nameToGroupingField = [];

        foreach ($summaryMetadata->getLeafDimensions() as $dimension) {
            $groupingFieldToDimension[$dimension->getGroupingField()] = $dimension;
            $nameToGroupingField[$dimension->getName()] = $dimension->getGroupingField();
        }

        return new self(
            groupingFieldToName: $summaryMetadata->getGroupingFields(),
            nameToGroupingField: $nameToGroupingField,
        );
    }

    /**
     * @return list<string>
     */
    public function getGroupingFields(): array
    {
        return array_keys($this->groupingFieldToName);
    }

    public function registerExpression(string $name, string $expression): void
    {
        if (\in_array($expression, $this->nameToExpression, true)) {
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

        foreach ($this->groupingFieldToName as $groupingField => $name) {
            $expression = $this->nameToExpression[$name]
                ?? throw new LogicException(\sprintf(
                    'Grouping field "%s" is not registered in the groupings. Make sure to register the expression for the grouping field before calling getExpression().',
                    $groupingField,
                ));

            $grouping[$groupingField] = $expression;
        }

        ksort($grouping);

        return 'REKALOGIKA_GROUPING_CONCAT(' . implode(', ', $grouping) . ')';
    }

    public function addSelected(string $name): void
    {
        if (!isset($this->nameToGroupingField[$name])) {
            throw new InvalidArgumentException(\sprintf(
                'Grouping field "%s" is not registered in the groupings. Make sure to register the grouping field before calling addSelected().',
                $name,
            ));
        }

        $this->selected[$name] = true;
    }

    public function isSelected(string $name): bool
    {
        if (!isset($this->nameToGroupingField[$name])) {
            throw new InvalidArgumentException(\sprintf(
                'Grouping field "%s" is not registered in the groupings. Make sure to register the grouping field before calling isSelected().',
                $name,
            ));
        }

        return isset($this->selected[$name]);
    }

    /**
     * @return list<string> The names of the selected grouping fields.
     */
    public function getSelected(): array
    {
        return array_keys($this->selected);
    }

    public function getGroupingStringForSelect(): string
    {
        $groupingFields = [];

        foreach ($this->getGroupingFields() as $groupingField) {
            $groupingFields[$groupingField] = true;
        }

        foreach ($this->selected as $name => $_) {
            if (!isset($this->nameToGroupingField[$name])) {
                throw new InvalidArgumentException(\sprintf(
                    'Field "%s" does not have a mapping to a grouping field.',
                    $name,
                ));
            }

            $groupingField = $this->nameToGroupingField[$name];
            $groupingFields[$groupingField] = false;
        }

        ksort($groupingFields);

        $groupingString = '';

        foreach ($groupingFields as $groupingField => $isSelected) {
            $groupingString .= $isSelected ? '1' : '0';
        }

        return $groupingString;
    }
}
