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

namespace Rekalogika\Analytics\PivotTable\Model;

use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\PivotTable\TableVisitor;

abstract readonly class Property
{
    final public function __construct(private TreeNode $node) {}

    abstract public function getContent(): mixed;

    /**
     * @template T
     * @param TableVisitor<T> $visitor
     * @return T
     */
    abstract public function accept(TableVisitor $visitor): mixed;

    final public function getNode(): TreeNode
    {
        return $this->node;
    }
}
