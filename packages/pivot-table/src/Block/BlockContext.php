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

final readonly class BlockContext
{
    private Keys $keys;

    /**
     * @param list<string> $pivotedKeys
     * @param list<string> $unpivotedKeys
     * @param list<string> $currentKeyPath
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     * @param int<0,max> $subtotalDepth 0 is not in subtotal, 1 is in subtotal of first level, and so on.
     * @param int<0,max> $blockDepth 0 is the root block, 1 is the child of the root block, and so on.
     */
    public function __construct(
        private TreeNode $rootNode,
        array $unpivotedKeys,
        array $pivotedKeys,
        private array $skipLegends,
        private array $createSubtotals,
        private int $subtotalDepth = 0,
        private int $blockDepth = 0,
        array $currentKeyPath = [],
    ) {
        $this->keys = new Keys(
            pivotedKeys: $pivotedKeys,
            unpivotedKeys: $unpivotedKeys,
            currentKeyPath: $currentKeyPath,
        );
    }

    //
    // withers
    //

    public function incrementSubtotal(): self
    {
        return new self(
            rootNode: $this->rootNode,
            pivotedKeys: $this->keys->getPivotedKeys(),
            unpivotedKeys: $this->keys->getUnpivotedKeys(),
            currentKeyPath: $this->keys->getCurrentKeyPath(),
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
            rootNode: $this->rootNode,
            pivotedKeys: $this->keys->getPivotedKeys(),
            unpivotedKeys: $this->keys->getUnpivotedKeys(),
            currentKeyPath: $this->keys->getCurrentKeyPath(),
            skipLegends: $this->skipLegends,
            createSubtotals: $this->createSubtotals,
            subtotalDepth: $this->subtotalDepth,
            blockDepth: $this->blockDepth + $amount,
        );
    }

    public function appendKey(string $key): self
    {
        $newPath = $this->keys->getCurrentKeyPath();
        $newPath[] = $key;

        return new self(
            rootNode: $this->rootNode,
            pivotedKeys: $this->keys->getPivotedKeys(),
            unpivotedKeys: $this->keys->getUnpivotedKeys(),
            currentKeyPath: $newPath,
            skipLegends: $this->skipLegends,
            createSubtotals: $this->createSubtotals,
            subtotalDepth: $this->subtotalDepth,
            blockDepth: $this->blockDepth,
        );
    }

    //
    // keys
    //

    /**
     * @return list<string>
     */
    public function getUnpivotedKeys(): array
    {
        return $this->keys->getUnpivotedKeys();
    }

    /**
     * @return list<string>
     */
    public function getPivotedKeys(): array
    {
        return $this->keys->getPivotedKeys();
    }

    /**
     * @return list<string>
     */
    public function getKeys(): array
    {
        return $this->keys->getKeys();
    }

    public function isKeyPivoted(string $key): bool
    {
        return $this->keys->isKeyPivoted($key);
    }

    public function isKeyUnpivoted(string $key): bool
    {
        return $this->keys->isKeyUnpivoted($key);
    }

    public function getFirstPivotedKey(): ?string
    {
        return $this->keys->getFirstPivotedKey();
    }

    /**
     * @return list<string>
     */
    public function getCurrentKeyPath(): array
    {
        return $this->keys->getCurrentKeyPath();
    }

    public function getCurrentKey(): ?string
    {
        return $this->keys->getCurrentKey();
    }

    /**
     * @param int<1,max> $level 1 means gets the next key, 2 means get the next
     * after the next key, and so on.
     * @return string|null
     */
    public function getNextKey(int $level = 1): ?string
    {
        return $this->keys->getNextKey($level);
    }

    public function isLeaf(string $key): bool
    {
        return $this->keys->isLeaf($key);
    }

    //
    // misc
    //

    public function isLegendSkipped(string $key): bool
    {
        return \in_array($key, $this->skipLegends, true);
    }

    public function doCreateSubtotals(string $key): bool
    {
        return \in_array($key, $this->createSubtotals, true);
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

    public function getRootTreeNode(): TreeNode
    {
        return $this->rootNode;
    }
}
