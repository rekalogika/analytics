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

use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;

/**
 * @implements \IteratorAggregate<DimensionPathMetadata>
 */
final readonly class DimensionHierarchyMetadata implements \IteratorAggregate
{
    /**
     * @var non-empty-list<DimensionPathMetadata>
     */
    private array $paths;

    /**
     * @var array<int,DimensionLevelMetadata>
     */
    private array $levels;

    private DimensionLevelMetadata $lowestLevel;

    /**
     * @var array<string,DimensionLevelPropertyMetadata> $properties
     */
    private array $properties;

    /**
     * @param class-string $hierarchyClass
     * @param non-empty-list<DimensionPathMetadata> $paths
     */
    public function __construct(
        private string $hierarchyClass,
        array $paths,
        private ?DimensionMetadata $dimensionMetadata = null,
    ) {
        $newPaths = [];
        $levels = [];
        $properties = [];

        foreach ($paths as $path) {
            $path = $path->withHierarchyMetadata($this);
            $newPaths[] = $path;

            foreach ($path as $level) {
                $levels[$level->getLevelId()] = $level;

                foreach ($level->getProperties() as $property) {
                    $properties[$property->getName()] = $property;
                }
            }
        }

        $this->paths = $newPaths;
        $this->levels = $levels;
        $this->properties = $properties;
        $this->lowestLevel = $this->paths[0]->getLowestLevel();
    }

    public function getDimensionMetadata(): DimensionMetadata
    {
        if ($this->dimensionMetadata === null) {
            throw new MetadataException('Dimension metadata not set');
        }

        return $this->dimensionMetadata;
    }

    public function withDimensionMetadata(DimensionMetadata $dimensionMetadata): self
    {
        return new self(
            hierarchyClass: $this->hierarchyClass,
            paths: $this->paths,
            dimensionMetadata: $dimensionMetadata,
        );
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->paths);
    }

    /**
     * @return class-string
     */
    public function getHierarchyClass(): string
    {
        return $this->hierarchyClass;
    }

    /**
     * @return list<DimensionPathMetadata>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    public function getPrimaryPath(): DimensionPathMetadata
    {
        return $this->paths[0];
    }

    /**
     * @return array<int,DimensionLevelMetadata>
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    public function getOneOfHighestLevels(): DimensionLevelMetadata
    {
        return $this->getPrimaryPath()->getHighestLevel();
    }

    public function getLowestLevel(): DimensionLevelMetadata
    {
        return $this->lowestLevel;
    }

    public function getLevel(int $level): DimensionLevelMetadata
    {
        return $this->levels[$level]
            ?? throw new MetadataException(\sprintf('Level not found: %d', $level));
    }

    public function getLowerLevel(?int $level): ?int
    {
        if ($level === null) {
            return $this->getOneOfHighestLevels()->getLevelId();
        }

        return $this->getLevel($level)->getLowerLevel()?->getLevelId();
    }

    /**
     * @return list<DimensionLevelPropertyMetadata>
     */
    public function getProperties(): array
    {
        return array_values($this->properties);
    }

    public function getProperty(string $name): DimensionLevelPropertyMetadata
    {
        return $this->properties[$name]
            ?? throw new MetadataException(\sprintf('Property not found: %s', $name));
    }

    /**
     * @return list<DimensionPathMetadata>
     */
    public function getPathMetadatasForLevel(int $levelId): array
    {
        $paths = [];

        foreach ($this->paths as $path) {
            if ($path->containsLevel($levelId)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function getPrimaryPathMetadataForLevel(int $levelId): DimensionPathMetadata
    {
        foreach ($this->paths as $path) {
            if ($path->containsLevel($levelId)) {
                return $path;
            }
        }

        throw new MetadataException(\sprintf('Path not found for level: %d', $levelId));
    }

    /**
     * @return array<string,bool>
     */
    public function getGroupingsByPropertyForSelect(
        string $property,
    ): array {
        $level = $this->getProperty($property)
            ->getLevelMetadata()
            ->getLevelId();

        return $this->getGroupingsByLevelForSelect($level);
    }

    /**
     * @return array<string,bool>
     */
    private function getGroupingsByLevelForSelect(
        int|null $level,
    ): array {
        if ($level === null) {
            return [];
        }

        $groupings = [];
        $properties = $this->getProperties();

        foreach ($properties as $property) {
            $groupings[$property->getName()] = true;
        }

        $path = $this->getPathMetadatasForLevel($level)[0];

        $reached = false;

        foreach ($path->getLevels() as $curLevel) {
            foreach ($curLevel->getProperties() as $property) {
                $groupings[$property->getName()] = $reached;
            }

            if ($curLevel->getLevelId() === $level) {
                $reached = true;
            }
        }

        return $groupings;
    }
}
