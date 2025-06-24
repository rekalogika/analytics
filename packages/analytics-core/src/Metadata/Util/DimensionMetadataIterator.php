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

namespace Rekalogika\Analytics\Metadata\Util;

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;

/**
 * @implements \RecursiveIterator<array-key,DimensionMetadata>
 */
final readonly class DimensionMetadataIterator implements \RecursiveIterator
{
    /**
     * @var \ArrayIterator<array-key,DimensionMetadata>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<array-key,DimensionMetadata> $dimensions
     */
    public function __construct(array $dimensions)
    {
        $this->iterator = new \ArrayIterator($dimensions);
    }

    #[\Override]
    public function hasChildren(): bool
    {
        if (!$this->iterator->valid()) {
            return false;
        }

        $current = $this->iterator->current();

        if ($current instanceof DimensionMetadata) {
            $children = $current->getChildren();

            return $children !== [];
        }

        return false;
    }

    /**
     * @return \RecursiveIterator<array-key,DimensionMetadata>
     */
    #[\Override]
    public function getChildren(): \RecursiveIterator
    {
        if (!$this->iterator->valid()) {
            return new self([]);
        }

        $current = $this->iterator->current();
        $children = $current?->getChildren(); // @phpstan-ignore-line

        if ($children === [] || $children === null) {
            return new self([]);
        }

        return new self($children);
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->iterator->current();
    }

    #[\Override]
    public function next(): void
    {
        $this->iterator->next();
    }

    #[\Override]
    public function key(): mixed
    {
        return $this->iterator->key();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
