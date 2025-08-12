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

    protected function getSubtotalNode(): ?CubeDecorator
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


    /**
     * @param null|non-empty-list<CubeDecorator> $prototypeCubes
     * @return iterable<CubeDecorator>
     */
    protected function getChildCubes(?array $prototypeCubes = null): iterable
    {
        if ($prototypeCubes === null) {
            $children = $this->cube->drillDownWithoutBalancing($this->getChildKey());
        } else {
            $children = $this->cube->drillDownWithPrototypes(
                dimensionName: $this->getChildKey(),
                prototypeCubes: $prototypeCubes,
            );
        }

        $children = iterator_to_array($children, preserve_keys: true);

        if (\count($children) >= 2) {
            $subtotalNode = $this->getSubtotalNode();

            if ($subtotalNode !== null) {
                $children[] = $subtotalNode;
            }
        }

        return $children;
    }

    /**
     * @param null|non-empty-list<CubeDecorator> $prototypeCubes
     */
    protected function getOneChildCube(?array $prototypeCubes = null): CubeDecorator
    {
        foreach ($this->getChildCubes($prototypeCubes) as $childNode) {
            return $childNode;
        }

        throw new \RuntimeException('No child nodes found in the current node.');
    }

    /**
     * @param null|non-empty-list<CubeDecorator> $prototypeCubes
     * @return iterable<Block>
     */
    protected function getChildBlocks(?array $prototypeCubes = null): iterable
    {
        $children = $this->getChildCubes($prototypeCubes);

        if ($children === []) {
            yield new EmptyBlockGroup(
                cube: $this->getCube(),
                context: $this->getContext(),
            );
        }

        foreach ($children as $childCube) {
            yield $this->createBlock($childCube);
        }
    }

    /**
     * @param null|non-empty-list<CubeDecorator> $prototypeCubes
     */
    protected function getOneChildBlock(?array $prototypeCubes = null): Block
    {
        foreach ($this->getChildBlocks($prototypeCubes) as $childBlock) {
            return $childBlock;
        }

        throw new \RuntimeException('No child blocks found in the current node.');
    }
}
