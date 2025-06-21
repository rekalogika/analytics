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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\Metadata\DimensionGroup;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\DimensionClassMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadataFactory;
use Rekalogika\Analytics\Metadata\Util\AttributeUtil;
use Rekalogika\Analytics\Metadata\Util\TranslatableUtil;

final readonly class DefaultDimensionMetadataFactory implements DimensionMetadataFactory
{
    private DimensionClassMetadataFactory $dimensionClassMetadataFactory;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AttributeCollectionFactory $attributeCollectionFactory,
        DimensionClassMetadataFactory $dimensionClassMetadataFactory,
    ) {
        $this->dimensionClassMetadataFactory = $dimensionClassMetadataFactory
            ->with($this);
    }

    /**
     * @param class-string $class
     */
    private function getManagerForClass(string $class): EntityManager
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);

        if (!$entityManager instanceof EntityManager) {
            throw new InvalidArgumentException(\sprintf(
                'Class "%s" is not managed by Doctrine ORM.',
                $class,
            ));
        }

        return $entityManager;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassMetadata<T>
     */
    private function getDoctrineMetadata(string $class): ClassMetadata
    {
        $entityManager = $this->getManagerForClass($class);

        return $entityManager->getClassMetadata($class);
    }

    /**
     * @param class-string $class
     * @param null|ValueResolver|string $source
     * @return ValueResolver
     */
    private function normalizeSourceProperty(
        string $class,
        string $property,
        null|ValueResolver|string $source,
    ): ValueResolver {
        if ($this->isDimensionGroup($class)) {
            if (!$source instanceof ValueResolver) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" is a DimensionGroup, therefore, the source of the property "%s" must be an instance of "%s", "%s" given.',
                    $class,
                    $property,
                    ValueResolver::class,
                    get_debug_type($source),
                ));
            }

            return $source;
        }

        if ($source === null) {
            $source = $property;
        }

        if (!$source instanceof ValueResolver) {
            $sourceClassMetadata = $this->getDoctrineMetadata($class);
            $sourceClassMetadata = new ClassMetadataWrapper($sourceClassMetadata);

            $isEntity = $sourceClassMetadata->isPropertyEntity($source);
            $isField = $sourceClassMetadata->isPropertyField($source);

            if ($isEntity) {
                $source = new IdentifierValue($source);
            } elseif ($isField) {
                $source = new PropertyValue($source);
            } else {
                throw new InvalidArgumentException(\sprintf(
                    'Source property "%s" of class "%s" is not a valid entity or field. It must be an entity or field.',
                    $source,
                    $class,
                ));
            }
        }

        return $source;
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

        $sourceProperty = $this->normalizeSourceProperty(
            class: $class,
            property: $property,
            source: $dimensionAttribute->getSource(),
        );

        // label

        $label = $dimensionAttribute->getLabel() ?? $property;
        $label = TranslatableUtil::normalize($label);

        $nullLabel = TranslatableUtil::normalize($dimensionAttribute->getNullLabel())
            ?? new TranslatableMessage('(None)');

        // typeClass

        $typeClass = AttributeUtil::getTypeClass($class, $property);

        // dimension class metadata

        if ($typeClass !== null && $this->isDimensionGroup($typeClass)) {
            $dimensionClassMetadata = $this->dimensionClassMetadataFactory
                ->getDimensionClassMetadata($typeClass);
        } else {
            $dimensionClassMetadata = null;
        }

        return new DimensionMetadata(
            valueResolver: $sourceProperty,
            propertyName: $property,
            label: $label,
            typeClass: $typeClass,
            nullLabel: $nullLabel,
            mandatory: $dimensionAttribute->isMandatory(),
            hidden: $dimensionAttribute->isHidden(),
            attributes: $propertyAttributes,
            groupingStrategy: $dimensionClassMetadata?->getGroupingStrategy(),
            children: $dimensionClassMetadata?->getDimensions() ?? [],
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
