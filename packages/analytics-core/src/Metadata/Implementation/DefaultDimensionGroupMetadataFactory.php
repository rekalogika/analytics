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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Attribute\Dimension;
use Rekalogika\Analytics\Metadata\Attribute\DimensionGroup;
use Rekalogika\Analytics\Metadata\Attribute\Summary;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Summary\DimensionGroupMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionGroupMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadataFactory;
use Rekalogika\Analytics\Metadata\Util\AttributeUtil;

final readonly class DefaultDimensionGroupMetadataFactory implements DimensionGroupMetadataFactory
{
    public function __construct(
        private AttributeCollectionFactory $attributeCollectionFactory,
        private ?DimensionMetadataFactory $dimensionMetadataFactory = null,
    ) {}

    private function getDimensionMetadataFactory(): DimensionMetadataFactory
    {
        if ($this->dimensionMetadataFactory === null) {
            throw new InvalidArgumentException('DimensionMetadataFactory is not set.');
        }

        return $this->dimensionMetadataFactory;
    }

    #[\Override]
    public function with(DimensionMetadataFactory $dimensionMetadataFactory): static
    {
        return new self(
            attributeCollectionFactory: $this->attributeCollectionFactory,
            dimensionMetadataFactory: $dimensionMetadataFactory,
        );
    }

    #[\Override]
    public function getDimensionGroupMetadata(
        string $class,
    ): DimensionGroupMetadata {
        $classAttributes = $this->attributeCollectionFactory
            ->getClassAttributes($class);

        if (
            !$classAttributes->hasAttribute(DimensionGroup::class)
            && !$classAttributes->hasAttribute(Summary::class)
        ) {
            throw new InvalidArgumentException(\sprintf(
                'Class "%s" does not have a DimensionGroup or Summary attribute.',
                $class,
            ));
        }

        $dimensionGroup = $classAttributes->getAttribute(DimensionGroup::class);

        $propertyReflections = AttributeUtil::getPropertiesOfClass($class);
        $dimensions = [];

        foreach ($propertyReflections as $propertyReflection) {
            $property = $propertyReflection->getName();

            // get attributes
            $propertyAttributes = $this->attributeCollectionFactory
                ->getPropertyAttributes($class, $property);

            $dimensionAttribute = $propertyAttributes->tryGetAttribute(Dimension::class);

            if (!$dimensionAttribute instanceof Dimension) {
                continue;
            }

            $dimensions[$property] = $this
                ->getDimensionMetadataFactory()
                ->createDimensionMetadata(
                    $class,
                    $property,
                );
        }

        if ($dimensions === []) {
            throw new InvalidArgumentException(\sprintf(
                'Class "%s" does not have any dimension properties.',
                $class,
            ));
        }

        return new DimensionGroupMetadata(
            class: $class,
            groupingStrategy: $dimensionGroup->getGroupingStrategy(),
            dimensions: $dimensions,
        );
    }
}
