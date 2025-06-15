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

namespace Rekalogika\Analytics\Metadata\DimensionHierarchy;

use Rekalogika\Analytics\Contracts\Metadata\Hierarchy;
use Rekalogika\Analytics\Contracts\Metadata\LevelProperty;
use Rekalogika\Analytics\Core\Exception\MetadataException;
use Rekalogika\Analytics\Core\Util\AttributeUtil;
use Rekalogika\Analytics\Core\Util\LiteralString;
use Rekalogika\Analytics\Core\Util\TranslatableMessage;
use Rekalogika\Analytics\Core\Util\TranslatableUtil;
use Rekalogika\Analytics\Metadata\DimensionHierarchyMetadataFactory;

final readonly class DefaultDimensionHierarchyMetadataFactory implements DimensionHierarchyMetadataFactory
{
    /**
     * @param class-string $hierarchyClass
     */
    #[\Override]
    public function getDimensionHierarchyMetadata(
        string $hierarchyClass,
    ): DimensionHierarchyMetadata {
        $hierarchyAttribute = AttributeUtil::getClassAttribute(
            class: $hierarchyClass,
            attributeClass: Hierarchy::class,
        ) ?? throw new MetadataException('DimensionHierarchy attribute is required, but not found');

        // collect properties & levels

        $properties = AttributeUtil::getPropertiesOfClass($hierarchyClass);

        $levels = [];

        foreach ($properties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $dimensionLevelAttribute = AttributeUtil::getPropertyAttribute(
                class: $hierarchyClass,
                property: $property,
                attributeClass: LevelProperty::class,
            );

            if ($dimensionLevelAttribute === null) {
                continue;
            }

            $level = $dimensionLevelAttribute->getLevel();
            $name = $property;
            $label = $dimensionLevelAttribute->getLabel() ?? $name;

            if (\is_string($label)) {
                $label = new LiteralString($label);
            }

            $valueResolver = $dimensionLevelAttribute->getValueResolver();
            $typeClass = AttributeUtil::getTypeClass($reflectionProperty);

            $nullLabel = TranslatableUtil::normalize($dimensionLevelAttribute->getNullLabel())
                ?? new TranslatableMessage('(None)');

            $dimensionPropertyMetadata = new DimensionLevelPropertyMetadata(
                name: $name,
                label: $label,
                valueResolver: $valueResolver,
                typeClass: $typeClass,
                nullLabel: $nullLabel,
                hidden: $dimensionLevelAttribute->isHidden(),
            );

            $levels[$level][] = $dimensionPropertyMetadata;
        }

        // create levels

        $dimensionLevelsMetadata = [];

        foreach ($levels as $level => $dimensionPropertyMetadatas) {
            $dimensionLevelsMetadata[$level] = new DimensionLevelMetadata(
                levelId: $level,
                properties: $dimensionPropertyMetadatas,
            );
        }

        // create paths

        $dimensionPathsMetadata = [];

        foreach ($hierarchyAttribute->getPaths() as $path) {
            $levels = [];

            foreach ($path as $level) {
                $levels[] = $dimensionLevelsMetadata[$level]
                    ?? throw new MetadataException(\sprintf('Level not found: %d', $level));
            }

            if ($levels === []) {
                throw new MetadataException('At least one level is required');
            }

            $dimensionPathsMetadata[] = new DimensionPathMetadata(
                levels: $levels,
            );
        }

        // make sure at least one path is defined

        if ($dimensionPathsMetadata === []) {
            throw new MetadataException('At least one path is required');
        }

        // ensure the lowest level of each path is the same

        $lowest = null;

        foreach ($dimensionPathsMetadata as $dimensionPathMetadata) {
            $lowestLevel = $dimensionPathMetadata->getLowestLevel()->getLevelId();

            if ($lowest === null) {
                $lowest = $lowestLevel;
            } elseif ($lowest !== $lowestLevel) {
                throw new MetadataException('All paths must have the same lowest level');
            }
        }

        // return the hierarchy metadata

        return new DimensionHierarchyMetadata(
            hierarchyClass: $hierarchyClass,
            paths: $dimensionPathsMetadata,
        );
    }
}
