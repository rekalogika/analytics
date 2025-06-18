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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Common\Exception\SummaryNotFound;
use Rekalogika\Analytics\Common\Model\LiteralString;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Contracts\Model\Partition as DoctrineSummaryPartition;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\Metadata\Groupings;
use Rekalogika\Analytics\Core\Metadata\Measure;
use Rekalogika\Analytics\Core\Metadata\Partition;
use Rekalogika\Analytics\Core\Metadata\PartitionKey;
use Rekalogika\Analytics\Core\Metadata\PartitionLevel;
use Rekalogika\Analytics\Core\Metadata\Summary;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollection;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadataFactory;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionPropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\PartitionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\Util\AttributeUtil;
use Rekalogika\Analytics\Metadata\Util\TranslatableUtil;

final readonly class DefaultSummaryMetadataFactory implements SummaryMetadataFactory
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private DimensionHierarchyMetadataFactory $dimensionHierarchyMetadataFactory,
        private AttributeCollectionFactory $attributeCollectionFactory,
    ) {}

    /**
     * @param class-string $className
     */
    #[\Override]
    public function isSummary(string $className): bool
    {
        return \in_array($className, $this->getSummaryClasses(), true);
    }

    #[\Override]
    public function getSummaryClasses(): array
    {
        $classes = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }

            $metadata = $manager->getMetadataFactory()->getAllMetadata();

            foreach ($metadata as $classMetadata) {
                $class = $classMetadata->getName();

                $hasAttribute = $this->attributeCollectionFactory
                    ->getClassAttributes($class)
                    ->hasAttribute(Summary::class);

                if (!$hasAttribute) {
                    continue;
                }

                $classes[$class] = true;
            }
        }

        return array_keys($classes);
    }

    /**
     * @todo remove $sourceClasses remnant, change to single $sourceClass
     * instead
     * @param class-string $summaryClassName
     */
    #[\Override]
    public function getSummaryMetadata(
        string $summaryClassName,
    ): SummaryMetadata {
        $reflectionClass = new \ReflectionClass($summaryClassName);

        // get summary attribute

        $summaryAttribute = $this->attributeCollectionFactory
            ->getClassAttributes($summaryClassName)
            ->getAttribute(Summary::class)
            ?? throw new SummaryNotFound($summaryClassName);

        $sourceClass = $summaryAttribute->getSourceClass();
        $sourceClassMetadata = $this->getDoctrineClassMetadata($sourceClass);
        $summaryClassMetadata = $this->getDoctrineClassMetadata($summaryClassName);

        $properties = AttributeUtil::getPropertiesOfClass($summaryClassName);

        $dimensionMetadatas = [];
        $measureMetadatas = [];
        $partitionMetadata = null;
        $groupingsProperty = null;

        $classAttributes = $this->attributeCollectionFactory
            ->getClassAttributes($summaryClassName);

        foreach ($properties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $propertyAttributes = $this->attributeCollectionFactory
                ->getPropertyAttributes($summaryClassName, $property);

            $dimensionAttribute = $propertyAttributes
                ->getAttribute(Dimension::class);

            $measureAttribute = $propertyAttributes
                ->getAttribute(Measure::class);

            $partitionAttribute = $propertyAttributes
                ->getAttribute(Partition::class);

            $groupingsAttribute = $propertyAttributes
                ->getAttribute(Groupings::class);

            $typeClass = AttributeUtil::getTypeClass($reflectionProperty);

            if ($dimensionAttribute !== null && $measureAttribute !== null) {
                throw new MetadataException('Property cannot have both Dimension and Measure attributes');
            }

            if ($dimensionAttribute !== null) {
                $dimensionMetadata = $this->createDimensionMetadata(
                    summaryProperty: $property,
                    dimensionAttribute: $dimensionAttribute,
                    summaryClass: $summaryClassName,
                    sourceClassMetadata: $sourceClassMetadata,
                    summaryClassMetadata: $summaryClassMetadata,
                    attributes: $propertyAttributes,
                    typeClass: $typeClass,
                );

                $dimensionMetadatas[$property] = $dimensionMetadata;
            } elseif ($measureAttribute !== null) {
                $measureMetadatas[$property] =
                    $this->createMeasureMetadata(
                        summaryClass: $summaryClassName,
                        property: $property,
                        measureAttribute: $measureAttribute,
                        attributes: $propertyAttributes,
                        typeClass: $typeClass,
                    );
            } elseif ($partitionAttribute !== null) {
                $partitionMetadata = $this->createPartitionMetadata(
                    summaryProperty: $property,
                    partitionAttribute: $partitionAttribute,
                    summaryClassMetadata: $summaryClassMetadata,
                    attributes: $propertyAttributes,
                );
            } elseif ($groupingsAttribute !== null) {
                $groupingsProperty = $property;
            }
        }

        if ($dimensionMetadatas === []) {
            throw new MetadataException('At least one Dimension attribute is required');
        }

        if ($measureMetadatas === []) {
            throw new MetadataException('At least one Measure attribute is required');
        }

        if ($partitionMetadata === null) {
            throw new MetadataException('Partition attribute is required');
        }

        if ($groupingsProperty === null) {
            throw new MetadataException('Groupings attribute is required');
        }

        $label = $summaryAttribute->getLabel() ?? $reflectionClass->getShortName();
        $label = TranslatableUtil::normalize($label);

        return new SummaryMetadata(
            sourceClass: $sourceClass,
            summaryClass: $summaryClassName,
            partition: $partitionMetadata,
            dimensions: $dimensionMetadatas,
            measures: $measureMetadatas,
            groupingsProperty: $groupingsProperty,
            attributes: $classAttributes,
            label: $label,
        );
    }

    /**
     * @param class-string $summaryClass
     * @param class-string|null $typeClass
     */
    private function createDimensionMetadata(
        string $summaryProperty,
        string $summaryClass,
        Dimension $dimensionAttribute,
        ClassMetadataWrapper $sourceClassMetadata,
        AttributeCollection $attributes,
        ?string $typeClass,
        ClassMetadataWrapper $summaryClassMetadata,
    ): DimensionMetadata {
        $sourceProperty = $dimensionAttribute->getSource();

        // if source property is not provided, use summary property name as
        // source property name

        if ($sourceProperty === null) {
            $sourceProperty = $summaryProperty;
        }

        // normalize source property

        if (!$sourceProperty instanceof ValueResolver) {
            $isEntity = $sourceClassMetadata->isPropertyEntity($sourceProperty);
            $isField = $sourceClassMetadata->isPropertyField($sourceProperty);

            if ($isEntity) {
                $sourceProperty = new IdentifierValue($sourceProperty);
            } elseif ($isField) {
                $sourceProperty = new PropertyValue($sourceProperty);
            } else {
                // @todo ensure validity
                $sourceProperty = new PropertyValue($sourceProperty);
            }
        }

        // label

        $label = $dimensionAttribute->getLabel() ?? $summaryProperty;
        $label = TranslatableUtil::normalize($label);

        $nullLabel = TranslatableUtil::normalize($dimensionAttribute->getNullLabel())
            ?? new TranslatableMessage('(None)');

        // hierarchy

        if ($summaryClassMetadata->isPropertyEmbedded($summaryProperty)) {
            $embeddedClass = $summaryClassMetadata
                ->getEmbeddedClassOfProperty($summaryProperty);

            $dimensionHierarchy = $this->dimensionHierarchyMetadataFactory
                ->getDimensionHierarchyMetadata($embeddedClass);

            $dimensionProperties = $this->createDimensionProperties(
                summaryClass: $summaryClass,
                summaryProperty: $summaryProperty,
                dimensionHierarchy: $dimensionHierarchy,
            );
        } else {
            $dimensionHierarchy = null;
            $dimensionProperties = [];
        }

        return new DimensionMetadata(
            valueResolver: $sourceProperty,
            summaryProperty: $summaryProperty,
            label: $label,
            hierarchy: $dimensionHierarchy,
            orderBy: $dimensionAttribute->getOrderBy(),
            typeClass: $typeClass,
            nullLabel: $nullLabel,
            mandatory: $dimensionAttribute->isMandatory(),
            hidden: $dimensionAttribute->isHidden(),
            attributes: $attributes,
            properties: $dimensionProperties,
        );
    }

    /**
     * @param class-string $summaryClass
     * @return array<string,DimensionPropertyMetadata>
     */
    private function createDimensionProperties(
        string $summaryClass,
        string $summaryProperty,
        DimensionHierarchyMetadata $dimensionHierarchy,
    ): array {
        $dimensionProperties = [];

        foreach ($dimensionHierarchy->getProperties() as $dimensionLevelProperty) {
            $attributes = $this->attributeCollectionFactory
                ->getPropertyAttributes($summaryClass, $dimensionLevelProperty->getName());

            $dimensionProperty = new DimensionPropertyMetadata(
                summaryProperty: $summaryProperty,
                hierarchyProperty: $dimensionLevelProperty->getName(),
                label: $dimensionLevelProperty->getLabel(),
                nullLabel: $dimensionLevelProperty->getNullLabel(),
                typeClass: $dimensionLevelProperty->getTypeClass(),
                dimensionLevelProperty: $dimensionLevelProperty,
                attributes: $attributes,
                hidden: $dimensionLevelProperty->isHidden(),
            );

            $dimensionProperties[$dimensionProperty->getSummaryProperty()] = $dimensionProperty;
        }

        return $dimensionProperties;
    }

    /**
     * @todo change $sourceProperty to be a single ValueResolver instead of an
     * array
     * @param Partition<mixed> $partitionAttribute
     */
    private function createPartitionMetadata(
        string $summaryProperty,
        Partition $partitionAttribute,
        AttributeCollection $attributes,
        ClassMetadataWrapper $summaryClassMetadata,
    ): PartitionMetadata {
        $sourceProperty = $partitionAttribute->getSource();

        if (!$summaryClassMetadata->isPropertyEmbedded($summaryProperty)) {
            throw new MetadataException('Partition property must be embedded');
        }

        $partitionClass = $summaryClassMetadata
            ->getEmbeddedClassOfProperty($summaryProperty);

        if (!is_a($partitionClass, DoctrineSummaryPartition::class, true)) {
            throw new MetadataException('Partition class must implement Partition interface');
        }

        // get partition level and partition id property names

        $partitionLevelPropertyName = null;
        $partitionKeyPropertyName = null;

        $properties = AttributeUtil::getPropertiesOfClass($partitionClass);

        foreach ($properties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $attributes = $this->attributeCollectionFactory
                ->getPropertyAttributes($partitionClass, $property);

            $partitionLevelAttribute = $attributes
                ->getAttribute(PartitionLevel::class);

            $partitionKeyAttribute = $attributes
                ->getAttribute(PartitionKey::class);

            if ($partitionLevelAttribute !== null) {
                if ($partitionLevelPropertyName !== null) {
                    throw new MetadataException('Multiple partition level properties found');
                }

                $partitionLevelPropertyName = $property;
            }

            if ($partitionKeyAttribute !== null) {
                if ($partitionKeyPropertyName !== null) {
                    throw new MetadataException('Multiple partition id properties found');
                }

                $partitionKeyPropertyName = $property;
            }
        }

        if ($partitionLevelPropertyName === null) {
            throw new MetadataException('Partition level property not found');
        }

        if ($partitionKeyPropertyName === null) {
            throw new MetadataException('Partition id property not found');
        }

        return new PartitionMetadata(
            source: $sourceProperty,
            summaryProperty: $summaryProperty,
            partitionClass: $partitionClass,
            partitionLevelProperty: $partitionLevelPropertyName,
            partitionKeyProperty: $partitionKeyPropertyName,
            attributes: $attributes,
        );
    }

    /**
     * @param class-string $summaryClass
     * @param class-string|null $typeClass
     */
    private function createMeasureMetadata(
        string $summaryClass,
        string $property,
        Measure $measureAttribute,
        AttributeCollection $attributes,
        ?string $typeClass,
    ): MeasureMetadata {
        $function = $measureAttribute->getFunction();

        $unit = $measureAttribute->getUnit();

        if ($unit !== null) {
            $unitSignature = sha1(serialize($unit));
        } else {
            $unitSignature = null;
        }

        $unit = TranslatableUtil::normalize($unit);

        // determine whether the measure is virtual or not

        $classMetadata = $this->getDoctrineClassMetadata($summaryClass);
        $virtual = !$classMetadata->hasProperty($property);

        $label = TranslatableUtil::normalize($measureAttribute->getLabel())
            ?? new LiteralString($property);

        return new MeasureMetadata(
            function: $function,
            summaryProperty: $property,
            label: $label,
            typeClass: $typeClass,
            attributes: $attributes,
            unit: $unit,
            unitSignature: $unitSignature,
            virtual: $virtual,
            hidden: $measureAttribute->isHidden(),
        );
    }

    /**
     * @param class-string $class
     */
    private function getDoctrineClassMetadata(string $class): ClassMetadataWrapper
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if ($manager === null) {
            throw new MetadataException(\sprintf('No manager found for class %s', $class));
        }

        $classMetadata = $manager->getClassMetadata($class);

        if (!$classMetadata instanceof ClassMetadata) {
            throw new MetadataException('Unsupported class metadata object returned');
        }

        return new ClassMetadataWrapper($classMetadata);
    }
}
