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

namespace Rekalogika\Analytics\PivotTable\Adapter;

use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\PivotTable\Util\PropertyMap;
use Rekalogika\PivotTable\Contracts\Tree\BranchNode;

final readonly class PivotTableAdapter implements BranchNode
{
    public static function adapt(TreeNode $node): self
    {
        return new self($node);
    }

    private function __construct(
        private TreeNode $node,
        private PropertyMap $propertyMap = new PropertyMap(),
    ) {}

    #[\Override]
    public function getKey(): string
    {
        return $this->node->getName();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->propertyMap->getLabel($this->node);
    }

    #[\Override]
    public function getField(): mixed
    {
        return $this->propertyMap->getMember($this->node);
    }

    #[\Override]
    public function getChildren(): iterable
    {
        foreach ($this->node as $item) {
            if ($item->isNull()) {
                continue;
            }

            if ($item->getMeasure() === null) {
                yield new PivotTableAdapter($item, $this->propertyMap);
            } else {
                yield new PivotTableAdapterLeaf($item, $this->propertyMap);
            }
        }
    }
}
