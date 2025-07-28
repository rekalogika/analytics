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

namespace Rekalogika\Analytics\Metadata\Implementation;

use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Metadata\Attribute\Dimension;
use Rekalogika\Analytics\Metadata\Attribute\DimensionGroup;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Summary\DimensionGroupMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadataFactory;
use Rekalogika\Analytics\Metadata\Util\AttributeUtil;
use Rekalogika\Analytics\Metadata\Util\TranslatableUtil;

final readonly class DefaultDimensionMetadataFactory implements DimensionMetadataFactory
{
    private DimensionGroupMetadataFactory $dimensionGroupMetadataFactory;

    public function __construct(
        private AttributeCollectionFactory $attributeCollectionFactory,
        DimensionGroupMetadataFactory $dimensionGroupMetadataFactory,
    ) {
        $this->dimensionGroupMetadataFactory = $dimensionGroupMetadataFactory
            ->with($this);
    }

    #[\Override]
    public function createDimensionMetadata(
        string $class,
        string $property,
    ): DimensionMetadata {
        // get attributes

        $propertyAttributes = $this->attributeCollectionFactory
            ->getPropertyAttributes($class, $property);

        $dimensionAttribute = $propertyAttributes->getAttribute(Dimension::class);

        // source property

        $valueResolver = $dimensionAttribute->getSource();

        // label

        $label = $dimensionAttribute->getLabel() ?? $property;
        $label = TranslatableUtil::normalize($label);

        $nullLabel = TranslatableUtil::normalize($dimensionAttribute->getNullLabel())
            ?? new TranslatableMessage('(None)');

        // typeClass

        $typeClass = AttributeUtil::getTypeClass($class, $property);

        // dimension class metadata

        if ($typeClass !== null && $this->isDimensionGroup($typeClass)) {
            $dimensionGroupMetadata = $this->dimensionGroupMetadataFactory
                ->getDimensionGroupMetadata($typeClass);
        } else {
            $dimensionGroupMetadata = null;
        }

        return new DimensionMetadata(
            valueResolver: $valueResolver,
            propertyName: $property,
            label: $label,
            typeClass: $typeClass,
            nullLabel: $nullLabel,
            orderBy: $dimensionAttribute->getOrderBy(),
            mandatory: $dimensionAttribute->isMandatory(),
            hidden: $dimensionAttribute->isHidden(),
            attributes: $propertyAttributes,
            groupingStrategy: $dimensionGroupMetadata?->getGroupingStrategy(),
            children: $dimensionGroupMetadata?->getDimensions() ?? [],
        );
    }

    /**
     * @param null|class-string $class
     */
    private function isDimensionGroup(?string $class): bool
    {
        if ($class === null) {
            return false;
        }

        $classAttributes = $this->attributeCollectionFactory
            ->getClassAttributes($class);

        return $classAttributes->hasAttribute(DimensionGroup::class);
    }
}
