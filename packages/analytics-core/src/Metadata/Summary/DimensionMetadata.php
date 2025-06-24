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
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\DimensionGroup\DimensionGroupAware;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollection;
use Rekalogika\Analytics\Metadata\Groupings\DefaultGroupByExpressions;
use Rekalogika\Analytics\Metadata\Implementation\DimensionAwareAttributeCollectionDecorator;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionMetadata extends PropertyMetadata implements \Stringable
{
    /**
     * @var array<string,DimensionMetadata>
     */
    private array $children;

    /**
     * @var array<string,DimensionMetadata>
     */
    private array $childrenByPropertyName;

    private ValueResolver $valueResolver;

    private string $dqlAlias;

    private Field|FieldSet|Cube|RollUp|GroupingSet $groupByExpression;

    /**
     * @param null|class-string $typeClass
     * @param array<string,DimensionMetadata> $children
     * @param Order|array<string,Order> $orderBy
     */
    public function __construct(
        string $propertyName,
        ValueResolver $valueResolver,
        TranslatableInterface $label,
        ?string $typeClass,
        private TranslatableInterface $nullLabel,
        private Order|array $orderBy,
        private bool $mandatory,
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
            mandatory: $this->mandatory,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            groupingStrategy: $this->groupingStrategy,
            children: $this->children,
            summaryMetadata: $summaryMetadata,
        );
    }

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
            mandatory: $this->mandatory,
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
     * @return Order|array<string,Order>
     */
    public function getOrderBy(): Order|array
    {
        return $this->orderBy;
    }

    /**
     * @todo deprecate this?
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function getParent(): ?self
    {
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
     * @param string $name Name is the property name of the child dimension. Not
     * the fully-qualified name.
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

    public function getGroupingStrategy(): ?GroupingStrategy
    {
        return $this->groupingStrategy;
    }

    public function getGroupByExpression(): Field|FieldSet|Cube|RollUp|GroupingSet
    {
        return $this->groupByExpression;
    }

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
