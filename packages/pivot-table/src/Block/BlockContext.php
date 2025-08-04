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

namespace Rekalogika\PivotTable\Block;

use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Rekalogika\PivotTable\Decorator\TreeNodeDecoratorRepository;

final readonly class BlockContext
{
    /**
     * @param list<string> $pivotedDimensions
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     * @param int<0,max> $subtotalDepth 0 is not in subtotal, 1 is in subtotal of first level, and so on.
     * @param int<0,max> $blockDepth 0 is the root block, 1 is the child of the root block, and so on.
     */
    public function __construct(
        private TreeNodeDecoratorRepository $repository,
        private array $pivotedDimensions = [],
        private array $skipLegends = [],
        private array $createSubtotals = [],
        private int $subtotalDepth = 0,
        private int $blockDepth = 0,
    ) {}

    public function getRepository(): TreeNodeDecoratorRepository
    {
        return $this->repository;
    }

    public function incrementSubtotal(): self
    {
        return new self(
            repository: $this->repository,
            pivotedDimensions: $this->pivotedDimensions,
            skipLegends: $this->skipLegends,
            createSubtotals: $this->createSubtotals,
            subtotalDepth: $this->subtotalDepth + 1,
            blockDepth: $this->blockDepth,
        );
    }

    /**
     * @param int<1,max> $amount
     */
    public function incrementBlockDepth(int $amount): self
    {
        return new self(
            repository: $this->repository,
            pivotedDimensions: $this->pivotedDimensions,
            skipLegends: $this->skipLegends,
            createSubtotals: $this->createSubtotals,
            subtotalDepth: $this->subtotalDepth,
            blockDepth: $this->blockDepth + $amount,
        );
    }

    public function isPivoted(TreeNodeDecorator $node): bool
    {
        return \in_array($node->getKey(), $this->pivotedDimensions, true);
    }

    /**
     * @return list<string>
     */
    public function getPivotedDimensions(): array
    {
        return $this->pivotedDimensions;
    }

    public function isLegendSkipped(TreeNodeDecorator $node): bool
    {
        return \in_array($node->getKey(), $this->skipLegends, true);
    }

    public function doCreateSubtotals(TreeNode $node): bool
    {
        return \in_array($node->getKey(), $this->createSubtotals, true);
    }

    /**
     * @return int<0,max>
     */
    public function getSubtotalDepth(): int
    {
        return $this->subtotalDepth;
    }

    /**
     * @return int<0,max>
     */
    public function getBlockDepth(): int
    {
        return $this->blockDepth;
    }
}
