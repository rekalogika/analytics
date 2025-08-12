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

use Rekalogika\PivotTable\Block\Model\CubeDecorator;

abstract class BlockGroup extends Block
{
    public function __construct(
        private readonly CubeDecorator $cube,
        BlockContext $context,
    ) {
        parent::__construct($context);
    }

    protected function getCube(): CubeDecorator
    {
        return $this->cube;
    }

    protected function getChildKey(): string
    {
        return $this->getContext()->getNextKey()
            ?? throw new \RuntimeException('Next key is not set.');
    }

    protected function getSubtotalCube(): ?CubeDecorator
    {
        $childKey = $this->getChildKey();

        // different values cannot be aggregated
        if ($childKey === '@values') {
            return null;
        }

        // If subtotals are not desired for this node, return null.
        if ($this->getContext()->doCreateSubtotalOnChildren() === false) {
            return null;
        }

        return $this->cube->asSubtotal($childKey);
    }

    //
    // cubes
    //

    /**
     * @var null|list<CubeDecorator>
     */
    private ?array $childCubes = null;

    /**
     * @return list<CubeDecorator>
     */
    protected function getChildCubes(): array
    {
        if ($this->childCubes !== null) {
            return $this->childCubes;
        }

        $prototypeCubes = $this->getPrototypeCubes();

        if ($prototypeCubes === []) {
            $children = $this->cube->drillDownWithoutBalancing($this->getChildKey());
        } else {
            $children = $this->cube->drillDownWithPrototypes(
                dimensionName: $this->getChildKey(),
                prototypeCubes: $prototypeCubes,
            );
        }

        $children = iterator_to_array($children, preserve_keys: true);

        if (\count($children) >= 2) {
            $subtotalNode = $this->getSubtotalCube();

            if ($subtotalNode !== null) {
                $children[] = $subtotalNode;
            }
        }

        return $this->childCubes = array_values($children);
    }

    protected function getOneChildCube(): CubeDecorator
    {
        foreach ($this->getChildCubes() as $childNode) {
            return $childNode;
        }

        throw new \RuntimeException('No child nodes found in the current node.');
    }

    //
    // blocks
    //

    /**
     * @var null|non-empty-list<Block>
     */
    private ?array $childBlocks = null;

    /**
     * @return non-empty-list<Block>
     */
    protected function getChildBlocks(): array
    {
        if ($this->childBlocks !== null) {
            return $this->childBlocks;
        }

        $children = $this->getChildCubes();

        if ($children === []) {
            return [
                new EmptyBlockGroup(
                    cube: $this->getCube(),
                    context: $this->getContext(),
                ),
            ];
        }

        $blocks = [];

        foreach ($children as $childCube) {
            $blocks[] = $this->createBlock($childCube);
        }

        /** @var non-empty-list<Block> $blocks */
        return $this->childBlocks = $blocks;
    }

    protected function getOneChildBlock(): Block
    {
        foreach ($this->getChildBlocks() as $childBlock) {
            return $childBlock;
        }

        // @phpstan-ignore-next-line
        throw new \RuntimeException('No child blocks found in the current node.');
    }

    //
    // prototype cubes
    //

    /**
     * @var null|list<CubeDecorator>
     */
    private ?array $prototypeCubes = null;

    /**
     * @return list<CubeDecorator>
     */
    final protected function getPrototypeCubes(): array
    {
        return $this->prototypeCubes ??= $this->createPrototypeCubes();
    }

    /**
     * Returns empty if no prototype cubes are defined.
     *
     * @return list<CubeDecorator>
     */
    abstract protected function createPrototypeCubes(): array;
}
