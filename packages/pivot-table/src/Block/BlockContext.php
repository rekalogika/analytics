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
     * @param list<string> $pivotedKeys
     * @param list<string> $unpivotedKeys
     * @param list<string> $currentKeyPath
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     * @param int<0,max> $subtotalDepth 0 is not in subtotal, 1 is in subtotal of first level, and so on.
     * @param int<0,max> $blockDepth 0 is the root block, 1 is the child of the root block, and so on.
     */
    public function __construct(
        private TreeNodeDecorator $rootNode,
        private TreeNodeDecoratorRepository $repository,
        private array $unpivotedKeys,
        private array $pivotedKeys,
        private array $skipLegends,
        private array $createSubtotals,
        private int $subtotalDepth = 0,
        private int $blockDepth = 0,
        private array $currentKeyPath = [],
    ) {
        if (
            array_diff($this->pivotedKeys, $this->unpivotedKeys) !== $this->pivotedKeys
            || array_diff($this->unpivotedKeys, $this->pivotedKeys) !== $this->unpivotedKeys
        ) {
            throw new \InvalidArgumentException(
                'Pivoted nodes and unpivoted nodes must not overlap.',
            );
        }
    }

    public function getRepository(): TreeNodeDecoratorRepository
    {
        return $this->repository;
    }

    //
    // withers
    //

    public function incrementSubtotal(): self
    {
        return new self(
            rootNode: $this->rootNode,
            repository: $this->repository,
            pivotedKeys: $this->pivotedKeys,
            unpivotedKeys: $this->unpivotedKeys,
            currentKeyPath: $this->currentKeyPath,
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
            repository: $this->repository,
            pivotedKeys: $this->pivotedKeys,
            unpivotedKeys: $this->unpivotedKeys,
            currentKeyPath: $this->currentKeyPath,
            skipLegends: $this->skipLegends,
            createSubtotals: $this->createSubtotals,
            subtotalDepth: $this->subtotalDepth,
            blockDepth: $this->blockDepth + $amount,
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
        return $this->unpivotedKeys;
    }

    /**
     * @return list<string>
     */
    public function getPivotedKeys(): array
    {
        return $this->pivotedKeys;
    }

    /**
     * @return list<string>
     */
    public function getKeys(): array
    {
        return array_merge($this->unpivotedKeys, $this->pivotedKeys);
    }

    public function isKeyPivoted(string $key): bool
    {
        return \in_array($key, $this->pivotedKeys, true);
    }

    public function isKeyUnpivoted(string $key): bool
    {
        return \in_array($key, $this->unpivotedKeys, true);
    }

    public function getFirstPivotedKey(): ?string
    {
        return $this->pivotedKeys[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getCurrentKeyPath(): array
    {
        return $this->currentKeyPath;
    }

    public function getCurrentKey(): ?string
    {
        if (\count($this->currentKeyPath) === 0) {
            return null;
        }

        return $this->currentKeyPath[\count($this->currentKeyPath) - 1] ?? null;
    }

    public function getNextKey(): ?string
    {
        $keys = $this->getKeys();
        $currentKey = $this->getCurrentKey();

        if ($currentKey === null) {
            return $keys[0] ?? null;
        }

        $currentIndex = array_search($currentKey, $keys, true);

        if ($currentIndex === false || $currentIndex + 1 >= \count($keys)) {
            return null;
        }

        return $keys[$currentIndex + 1];
    }

    //
    // misc
    //

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

    public function getRootTreeNode(): TreeNodeDecorator
    {
        return $this->rootNode;
    }
}
