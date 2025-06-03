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

/**
 * @implements \IteratorAggregate<DimensionLevelPropertyMetadata>
 */
final readonly class DimensionLevelMetadata implements \IteratorAggregate
{
    /**
     * @var non-empty-list<DimensionLevelPropertyMetadata>
     */
    private array $properties;

    /**
     * @param non-empty-list<DimensionLevelPropertyMetadata> $properties
     */
    public function __construct(
        private int $levelId,
        array $properties,
        private ?DimensionPathMetadata $pathMetadata = null,
    ) {
        $newProperties = [];

        foreach ($properties as $property) {
            $newProperties[] = $property->withLevelMetadata($this);
        }

        $this->properties = $newProperties;
    }

    public function withPathMetadata(DimensionPathMetadata $pathMetadata): self
    {
        return new self(
            $this->levelId,
            $this->properties,
            $pathMetadata,
        );
    }

    public function getPathMetadata(): DimensionPathMetadata
    {
        if ($this->pathMetadata === null) {
            throw new MetadataException('Path metadata is not set');
        }

        return $this->pathMetadata;
    }

    public function getPrimaryProperty(): DimensionLevelPropertyMetadata
    {
        return $this->properties[0];
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->properties);
    }

    public function getLevelId(): int
    {
        return $this->levelId;
    }

    /**
     * @return list<DimensionLevelPropertyMetadata>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getLowerLevel(): ?DimensionLevelMetadata
    {
        return $this->getPathMetadata()->getLowerLevel($this);
    }
}
