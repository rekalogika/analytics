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

namespace Rekalogika\Analytics\Metadata\Summary;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Hierarchy\HierarchyAware;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollection;
use Rekalogika\Analytics\Metadata\Groupings\DefaultGroupByExpressions;
use Rekalogika\Analytics\Metadata\Summary\Util\GroupingFieldsHelper;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionMetadata extends PropertyMetadata
{
    /**
     * @var array<string,DimensionMetadata>
     */
    private array $children;

    private ValueResolver $valueResolver;

    private string $dqlAlias;

    private Field|FieldSet|Cube|RollUp|GroupingSet $groupByExpression;

    /**
     * @var array<string,string>|null
     */
    private ?array $groupingFields;

    /**
     * @param null|class-string $typeClass
     * @param array<string,DimensionMetadata> $children
     *
     */
    public function __construct(
        string $propertyName,
        ValueResolver $valueResolver,
        TranslatableInterface $label,
        ?string $typeClass,
        private TranslatableInterface $nullLabel,
        private bool $mandatory,
        bool $hidden,
        AttributeCollection $attributes,
        private ?GroupingStrategy $groupingStrategy,
        array $children,
        private ?self $parent = null,
        ?string $parentPath = null,
        ?ValueResolver $parentValueResolver = null,
        ?SummaryMetadata $summaryMetadata = null,
        private ?string $groupingField = null,
    ) {
        // name

        if ($parentPath !== null) {
            $name = $parentPath . '.' . $propertyName;
        } else {
            $name = $propertyName;
        }

        // dqlAlias

        $this->dqlAlias = \sprintf(
            'dim_%s',
            hash('xxh128', $name),
        );

        // valueResolver

        if ($parentValueResolver !== null) {
            if (!$valueResolver instanceof HierarchyAware) {
                throw new LogicException(\sprintf(
                    'Value resolver for dimension "%s" must implement "%s" interface because it is a child of another dimension.',
                    $name,
                    HierarchyAware::class,
                ));
            }

            $valueResolver = $valueResolver->withInput($parentValueResolver);
        }

        $this->valueResolver = $valueResolver;

        // children

        $newChildren = [];

        foreach ($children as $child) {
            $groupingField = $this->groupingStrategy
                ?->getAssociatedGroupingField($child->getPropertyName())
                ?? $propertyName;

            if ($this->groupingField !== null) {
                $groupingField = \sprintf(
                    '%s.%s',
                    $this->groupingField,
                    $groupingField,
                );
            }

            $child = $child->withParent(
                parent: $this,
                parentPath: $name,
                parentValueResolver: $valueResolver,
                groupingField: $groupingField,
            );

            $newChildren[$child->getPropertyName()] = $child;
        }

        $this->children = $newChildren;

        // group by expression

        if ($this->groupingStrategy !== null) {
            $mappings = [];

            foreach ($this->children as $key => $child) {
                $mappings[$key] = $child->getGroupByExpression();
            }

            $mappings = new DefaultGroupByExpressions($mappings);

            $this->groupByExpression = $this->groupingStrategy
                ->getGroupByExpression($mappings);
        } else {
            $this->groupByExpression = new Field($this->dqlAlias);
        }

        // grouping field mappings

        if ($this->groupingStrategy !== null) {
            $groupingFields = GroupingFieldsHelper::getGroupingFields(
                children: $this->children,
                groupingStrategy: $this->groupingStrategy,
            );
        } else {
            $groupingFields = null;
        }

        $this->groupingFields = $groupingFields;

        // parent constructor

        parent::__construct(
            name: $name,
            propertyName: $propertyName,
            label: $label,
            typeClass: $typeClass,
            hidden: $hidden,
            attributes: $attributes,
            involvedSourceProperties: $valueResolver->getInvolvedProperties(),
            summaryMetadata: $summaryMetadata,
        );
    }

    public function withSummaryMetadata(
        SummaryMetadata $summaryMetadata,
        string $groupingField,
    ): self {
        return new self(
            propertyName: $this->getPropertyName(),
            valueResolver: $this->valueResolver,
            label: $this->getLabel(),
            typeClass: $this->getTypeClass(),
            nullLabel: $this->nullLabel,
            mandatory: $this->mandatory,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            groupingStrategy: $this->groupingStrategy,
            groupingField: $groupingField,
            children: $this->children,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function withParent(
        self $parent,
        string $parentPath,
        ValueResolver $parentValueResolver,
        string $groupingField,
    ): self {
        try {
            $summaryMetadata = $this->getSummaryMetadata();
        } catch (MetadataException) {
            $summaryMetadata = null;
        }

        return new self(
            propertyName: $this->getPropertyName(),
            valueResolver: $this->valueResolver,
            label: $this->getLabel(),
            typeClass: $this->getTypeClass(),
            nullLabel: $this->nullLabel,
            mandatory: $this->mandatory,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            groupingStrategy: $this->groupingStrategy,
            groupingField: $groupingField,
            children: $this->children,
            parent: $parent,
            parentPath: $parentPath,
            parentValueResolver: $parentValueResolver,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function getValueResolver(): ValueResolver
    {
        return $this->valueResolver;
    }

    public function getDqlAlias(): string
    {
        return $this->dqlAlias;
    }

    public function getNullLabel(): TranslatableInterface
    {
        return $this->nullLabel;
    }

    /**
     * @todo deprecate this?
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function getParent(): self
    {
        if ($this->parent === null) {
            throw new LogicException('DimensionMetadata does not have a parent.');
        }

        return $this->parent;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    /**
     * @return array<string,DimensionMetadata>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    /**
     * @return iterable<string,DimensionMetadata>
     */
    public function getDescendants(): iterable
    {
        foreach ($this->children as $child) {
            yield $child->getName() => $child;

            if ($child->hasChildren()) {
                yield from $child->getDescendants();
            }
        }
    }

    public function getGroupingStrategy(): ?GroupingStrategy
    {
        return $this->groupingStrategy;
    }

    public function getGroupByExpression(): Field|FieldSet|Cube|RollUp|GroupingSet
    {
        return $this->groupByExpression;
    }

    /**
     * @return null|array<string,string>
     */
    public function getGroupingFields(): ?array
    {
        return $this->groupingFields;
    }

    public function getGroupingField(): string
    {
        if ($this->groupingField === null) {
            throw new LogicException(\sprintf(
                'Dimension "%s" does not have a grouping field.',
                $this->getName(),
            ));
        }

        return $this->groupingField;
    }
}
