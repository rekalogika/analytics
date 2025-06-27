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

use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\PivotTable\Model\PropertyMap;
use Rekalogika\PivotTable\Contracts\LeafNode;

final readonly class PivotTableAdapterLeaf implements LeafNode
{
    public function __construct(
        private TreeNode $node,
        private PropertyMap $propertyMap,
    ) {
        $measure = $node->getMeasure();

        if ($measure === null) {
            throw new UnexpectedValueException(\sprintf(
                'Leaf node "%s" does not have a measure',
                $node->getName(),
            ));
        }
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->propertyMap->getValue($this->node);
    }

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
    public function getItem(): mixed
    {
        return $this->propertyMap->getMember($this->node);
    }
}
