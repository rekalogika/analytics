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

    /**
     * @return list<string>
     */
    public function getGroupingFields(): array
    {
        return $this->groupingFields;
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

        foreach ($this->groupingFields as $groupingField) {
            $expression = $this->nameToExpression[$groupingField]
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
        $this->selected[$name] = true;
    }

    public function isSelected(string $name): bool
    {
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
        $groupBySelector =
            new GroupBySelector($this->summaryMetadata->getGroupByExpression());

        foreach ($this->getSelected() as $name) {
            $dimension = $this->summaryMetadata->getDimension($name);
            $alias = $dimension->getDqlAlias();

            $groupBySelector->select($alias);
        }

        $result = $groupBySelector->getSelectedFields();

        $groupingFields = [];

        foreach ($this->groupingFields as $groupingField) {
            $groupingFields[$groupingField] = '1';
        }

        foreach ($result as $field) {
            $dimension = $this->summaryMetadata->getDimensionByAlias($field->getContent());
            $groupingFields[$dimension->getName()] = '0';
        }

        ksort($groupingFields);

        return implode('', $groupingFields);
    }
}
