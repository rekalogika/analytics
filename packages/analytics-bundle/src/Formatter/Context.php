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

namespace Rekalogika\Analytics\Bundle\Formatter;

use Rekalogika\Analytics\Contracts\TreeNode;

class Context
{
    public function __construct(
        private TreeNode $node,
        private PropertyType $type,
    ) {}

    public function getNode(): TreeNode
    {
        return $this->node;
    }

    public function getType(): PropertyType
    {
        return $this->type;
    }
}
