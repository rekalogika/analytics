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

namespace Rekalogika\PivotTable\Decorator;

final class ItemToTreeNodeDecoratorMap
{
    /**
     * @var array<string,TreeNodeDecorator>
     */
    private array $map = [];

    /**
     * @param iterable<TreeNodeDecorator> $nodes
     */
    public static function create(iterable $nodes): self
    {
        $instance = new self();

        foreach ($nodes as $node) {
            $instance->add($node);
        }

        return $instance;
    }

    public function add(TreeNodeDecorator $node): void
    {
        /** @psalm-suppress MixedAssignment */
        $item = $node->getItem();
        $key = $this->getKey($item);

        if (isset($this->map[$key])) {
            throw new \InvalidArgumentException(
                \sprintf('Item with key "%s" already exists in the map.', $key),
            );
        }

        $this->map[$key] = $node;
    }

    public function exists(mixed $item): bool
    {
        $key = $this->getKey($item);

        return isset($this->map[$key]);
    }

    public function get(mixed $item): TreeNodeDecorator
    {
        $key = $this->getKey($item);

        return $this->map[$key] ?? throw new \InvalidArgumentException(
            \sprintf('Item with key "%s" does not exist in the map.', $key),
        );
    }

    private function getKey(mixed $item): string
    {
        if (\is_object($item)) {
            return (string) spl_object_id($item);
        }

        return hash('xxh128', serialize($item));
    }
}
