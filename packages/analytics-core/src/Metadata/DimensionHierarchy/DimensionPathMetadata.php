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

use Rekalogika\Analytics\Common\Exception\MetadataException;

/**
 * @implements \IteratorAggregate<DimensionLevelMetadata>
 */
final readonly class DimensionPathMetadata implements \IteratorAggregate
{
    /**
     * @var non-empty-list<DimensionLevelMetadata>
     */
    private array $levels;

    /**
     * @var list<DimensionLevelPropertyMetadata> $properties
     */
    private array $properties;

    /**
     * @param non-empty-list<DimensionLevelMetadata> $levels
     */
    public function __construct(
        array $levels,
        private ?DimensionHierarchyMetadata $hierarchyMetadata = null,
    ) {
        $newLevels = [];
        $properties = [];

        foreach ($levels as $level) {
            $level = $level->withPathMetadata($this);
            $newLevels[] = $level;

            foreach ($level->getProperties() as $property) {
                $properties[] = $property;
            }
        }

        $this->levels = $newLevels;
        $this->properties = $properties;
    }

    public function withHierarchyMetadata(DimensionHierarchyMetadata $hierarchyMetadata): self
    {
        return new self(
            levels: $this->levels,
            hierarchyMetadata: $hierarchyMetadata,
        );
    }

    public function getHierarchyMetadata(): DimensionHierarchyMetadata
    {
        if ($this->hierarchyMetadata === null) {
            throw new MetadataException('Hierarchy metadata is not set');
        }

        return $this->hierarchyMetadata;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->levels);
    }

    /**
     * @return list<DimensionLevelPropertyMetadata>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function containsLevel(int $levelId): bool
    {
        foreach ($this->levels as $level) {
            if ($level->getLevelId() === $levelId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<DimensionLevelMetadata>
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    public function getHighestLevel(): DimensionLevelMetadata
    {
        return $this->levels[0];
    }

    public function getLowestLevel(): DimensionLevelMetadata
    {
        return $this->levels[\count($this->levels) - 1];
    }

    public function getLowerLevel(DimensionLevelMetadata $level): ?DimensionLevelMetadata
    {
        $index = array_search($level, $this->levels, true);

        if ($index === false) {
            return null;
        }

        return $this->levels[$index + 1] ?? null;
    }
}
