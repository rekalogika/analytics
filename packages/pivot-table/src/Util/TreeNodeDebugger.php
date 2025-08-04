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

namespace Rekalogika\PivotTable\Util;

use Rekalogika\Analytics\Contracts\Translation\NullTranslator;
use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class TreeNodeDebugger
{
    /**
     * @return array<string,mixed>
     */
    public static function debug(TreeNode $node): array
    {
        return (new self($node))->toArray();
    }

    public function __construct(private TreeNode $node) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $result = [
            'key' => $this->node->getKey(),
            'legend' => $this->normalizeItem($this->node->getLegend()),
            'item' => $this->normalizeItem($this->node->getItem()),
            'value' => $this->normalizeItem($this->node->getValue()),
            'isLeaf' => $this->node->isLeaf(),
            'children' => $this->normalizeChildren($this->node->getChildren()),
        ];

        // if ($this->node instanceof TreeNodeDecorator) {
        //     $result['parent'] = $this->node->getParent()
        //         ? (new self($this->node->getParent()))->toArray()
        //         : null;
        // }

        return $result;
    }

    /**
     * @param iterable<TreeNode> $children
     * @return list<array<string,mixed>>
     */
    private function normalizeChildren(iterable $children): array
    {
        $result = [];

        foreach ($children as $child) {
            $result[] = (new self($child))->toArray();
        }

        return $result;
    }

    private function normalizeItem(mixed $item): mixed
    {
        if (\is_array($item)) {
            return array_map(fn(mixed $i): mixed => $this->normalizeItem($i), $item);
        }

        if (\is_scalar($item)) {
            return var_export($item, true);
        }

        if (\is_null($item)) {
            return 'null';
        }

        if ($item instanceof \UnitEnum) {
            if ($item instanceof \BackedEnum) {
                return $item->value;
            }

            return $item->name;
        }

        if (!\is_object($item)) {
            return get_debug_type($item);
        }

        if ($item instanceof \Stringable) {
            return (string) $item;
        }

        if (method_exists($item, 'getContent')) {
            return $this->normalizeItem($item->getContent());
        }

        // @phpstan-ignore phpat.testPackageRekalogikaPivotTable
        if ($item instanceof TranslatableInterface) {
            // @phpstan-ignore phpat.testPackageRekalogikaPivotTable
            return $item->trans(new NullTranslator());
        }

        return \sprintf(
            '%s(%s)',
            get_debug_type($item),
            spl_object_id($item),
        );
    }
}
