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

use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Symfony\Component\VarExporter\VarExporter;

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

        if ($this->node instanceof TreeNodeDecorator) {
            $result['balancedChildren'] = $this->normalizeChildren($this->node->getBalancedChildren());
        }

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
        if (is_array($item)) {
            return array_map(fn(mixed $i): mixed => $this->normalizeItem($i), $item);
        }

        if (is_scalar($item)) {
            return var_export($item, true);
        }

        if (is_object($item)) {
            return get_class($item) . ':' . spl_object_id($item);
        }

        if (is_null($item)) {
            return 'null';
        }

        return \get_debug_type($item);
    }
}
