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

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\DimensionGroup\DimensionGroupAware;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Rekalogika\Analytics\Metadata\Groupings\DefaultGroupByExpressions;
use Rekalogika\Analytics\Metadata\Implementation\DimensionAwareAttributeCollectionDecorator;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Metadata of a dimension, at any level of the dimension tree. A dimension
 * holding a dimension group has the group's dimensions as children, and its
 * grouping strategy decides which combinations of them are stored. A
 * dimension without children is a leaf: it has a column in the summary table,
 * and can be used in queries. The string form of the metadata is the property
 * name.
 *
 * The metadata is immutable. A dimension is first created standalone, then
 * re-created with its parent and its summary through `withParent()` and
 * `withSummaryMetadata()` as the tree is assembled from the leaves up.
 */
final readonly class DimensionMetadata extends PropertyMetadata implements \Stringable
{
    /**
     * The children, keyed by fully qualified name.
     *
     * @var array<string,DimensionMetadata>
     */
    private array $children;

    /**
     * The children, keyed by property name.
     *
     * @var array<string,DimensionMetadata>
     */
    private array $childrenByPropertyName;

    private ValueResolver $valueResolver;

    private string $dqlAlias;

    private HierarchyAwareLabel $label;

    private Field|FieldSet|Cube|RollUp|GroupingSet $groupByExpression;

    /**
     * @param string $propertyName The name of the PHP property.
     * @param ValueResolver $valueResolver How the value is derived from the
     * source entity. For a child dimension, it is combined with
     * `$parentValueResolver` through `DimensionGroupAware::withInput()`.
     * @param TranslatableInterface $label The label of this dimension alone,
     * without the labels of its ancestors.
     * @param null|class-string $typeClass The class of the property type.
     * @param TranslatableInterface $nullLabel The label shown for a null
     * member.
     * @param Order|array<string,Order> $orderBy The default ordering of the
     * members, see the `Dimension` attribute.
     * @param bool $hidden Whether the dimension is hidden from the user
     * interface.
     * @param AttributeCollection $attributes The attributes of the property.
     * @param GroupingStrategy|null $groupingStrategy The strategy of the
     * dimension group held by this dimension, or null for a leaf.
     * @param array<string,DimensionMetadata> $children The dimensions of the
     * group held by this dimension, or an empty array for a leaf.
     * @param self|null $parent The dimension holding the group this dimension
     * belongs to, or null for a root dimension.
     * @param string|null $parentPath The fully qualified name of the parent,
     * prepended to the property name to form this dimension's name.
     * @param ValueResolver|null $parentValueResolver The value resolver of
     * the parent, fed into this dimension's resolver.
     * @param SummaryMetadata|null $summaryMetadata The owning summary.
     * @throws LogicException If a child dimension's value resolver does not
     * implement `DimensionGroupAware`.
     */
    public function __construct(
        string $propertyName,
        ValueResolver $valueResolver,
        TranslatableInterface $label,
        ?string $typeClass,
        private TranslatableInterface $nullLabel,
        private Order|array $orderBy,
        bool $hidden,
        AttributeCollection $attributes,
        private ?GroupingStrategy $groupingStrategy,
        array $children,
        private ?self $parent = null,
        ?string $parentPath = null,
        ?ValueResolver $parentValueResolver = null,
        ?SummaryMetadata $summaryMetadata = null,
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
            if (!$valueResolver instanceof DimensionGroupAware) {
                throw new LogicException(\sprintf(
                    'Value resolver for dimension "%s" must implement "%s" interface because it is a child of another dimension.',
                    $name,
                    DimensionGroupAware::class,
                ));
            }

            $valueResolver = $valueResolver->withInput($parentValueResolver);
        }

        $this->valueResolver = $valueResolver;

        // children

        $newChildren = [];
        $newChildrenByPropertyName = [];

        foreach ($children as $child) {
            if ($summaryMetadata !== null) {
                $child = $child->withSummaryMetadata(
                    summaryMetadata: $summaryMetadata,
                );
            }

            $child = $child->withParent(
                parent: $this,
                parentPath: $name,
                parentValueResolver: $valueResolver,
            );

            $newChildren[$child->getName()] = $child;
            $newChildrenByPropertyName[$child->getPropertyName()] = $child;
        }

        $this->children = $newChildren;
        $this->childrenByPropertyName = $newChildrenByPropertyName;

        // group by expression

        if ($this->groupingStrategy !== null) {
            $mappings = [];

            foreach ($this->childrenByPropertyName as $key => $child) {
                $mappings[$key] = $child->getGroupByExpression();
            }

            $mappings = new DefaultGroupByExpressions($mappings);

            $this->groupByExpression = $this->groupingStrategy
                ->getGroupByExpression($mappings);
        } else {
            $this->groupByExpression = new Field($this->dqlAlias);
        }

        // label

        $this->label = new HierarchyAwareLabel($this);

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

    #[\Override]
    public function __toString(): string
    {
        return $this->getPropertyName();
    }

    /**
     * The label of the dimension, aware of the labels of its ancestors, so the
     * caller can choose to show e.g. "Time / Civil / Year" or only "Year".
     * Translating it directly gives the label of this dimension alone.
     */
    #[\Override]
    public function getLabel(): HierarchyAwareLabel
    {
        return $this->label;
    }

    /**
     * The label of this dimension alone, as given in the attribute.
     *
     * @internal
     */
    public function getRawLabel(): TranslatableInterface
    {
        return parent::getLabel();
    }

    /**
     * Returns a copy of this dimension, and of its descendants, attached to
     * the given summary. Called by `SummaryMetadata` on the root dimensions.
     */
    public function withSummaryMetadata(
        SummaryMetadata $summaryMetadata,
    ): self {
        return new self(
            propertyName: $this->getPropertyName(),
            valueResolver: $this->valueResolver,
            label: $this->getLabel(),
            typeClass: $this->getTypeClass(),
            nullLabel: $this->nullLabel,
            orderBy: $this->orderBy,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            groupingStrategy: $this->groupingStrategy,
            children: $this->children,
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * Returns a copy of this dimension placed under the given parent. The
     * copy gets its fully qualified name from the parent's path, and its
     * value resolver takes the parent's resolver as input. Called by the
     * parent's constructor on each of its children.
     */
    public function withParent(
        self $parent,
        string $parentPath,
        ValueResolver $parentValueResolver,
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
            orderBy: $this->orderBy,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            groupingStrategy: $this->groupingStrategy,
            children: $this->children,
            parent: $parent,
            parentPath: $parentPath,
            parentValueResolver: $parentValueResolver,
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * How the value of the dimension is derived from the source entity. For a
     * child dimension, this already incorporates the resolvers of its
     * ancestors.
     */
    public function getValueResolver(): ValueResolver
    {
        return $this->valueResolver;
    }

    /**
     * The identifier the framework uses for this dimension in the DQL queries
     * it generates. Derived from the fully qualified name, so it is stable
     * and safe to use as an alias.
     */
    public function getDqlAlias(): string
    {
        return $this->dqlAlias;
    }

    /**
     * The label shown in place of a null member.
     */
    public function getNullLabel(): TranslatableInterface
    {
        return $this->nullLabel;
    }

    /**
     * The default ordering of the members, see the `Dimension` attribute.
     *
     * @return Order|array<string,Order>
     */
    public function getOrderBy(): Order|array
    {
        return $this->orderBy;
    }

    /**
     * The dimension holding the group this dimension belongs to, or null for
     * a root dimension.
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Returns true if this is not a root dimension.
     */
    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    /**
     * The direct children of this dimension, keyed by fully qualified name.
     * Empty for a leaf dimension.
     *
     * @return array<string,DimensionMetadata>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Returns true if this dimension holds a dimension group, i.e. it is not
     * a leaf.
     */
    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    /**
     * Returns a direct child by its property name.
     *
     * @param string $name Name is the property name of the child dimension. Not
     * the fully-qualified name.
     * @throws LogicException If there is no such child.
     */
    public function getChild(string $name): DimensionMetadata
    {
        return $this->childrenByPropertyName[$name]
            ?? throw new LogicException(\sprintf(
                'Dimension "%s" does not have child dimension "%s".',
                $this->getName(),
                $name,
            ));
    }

    /**
     * Returns every dimension below this one, at any depth, keyed by fully
     * qualified name, in depth-first order.
     *
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

    /**
     * Returns the leaf dimensions below this one, keyed by fully qualified
     * name. Does not include this dimension itself, even if it is a leaf.
     *
     * @return iterable<string,DimensionMetadata>
     */
    public function getLeaves(): iterable
    {
        foreach ($this->getDescendants() as $child) {
            if (!$child->hasChildren()) {
                yield $child->getName() => $child;
            }
        }
    }

    /**
     * The grouping strategy of the dimension group held by this dimension, or
     * null for a leaf dimension.
     */
    public function getGroupingStrategy(): ?GroupingStrategy
    {
        return $this->groupingStrategy;
    }

    /**
     * The group by expression of this dimension, as used by the strategy of
     * its parent. A plain `Field` for a leaf dimension, or the result of this
     * dimension's own grouping strategy applied to its children.
     */
    public function getGroupByExpression(): Field|FieldSet|Cube|RollUp|GroupingSet
    {
        return $this->groupByExpression;
    }

    /**
     * The attributes of the property. For a child dimension, the attributes
     * of its parent are used as a fallback, so an attribute on the property
     * holding a group applies to the dimensions inside the group.
     */
    #[\Override]
    public function getAttributes(): AttributeCollection
    {
        $parent = $this->getParent();

        if ($parent === null) {
            return parent::getAttributes();
        }

        return new DimensionAwareAttributeCollectionDecorator(
            decorated: parent::getAttributes(),
            dimensionMetadata: $parent,
        );
    }
}
