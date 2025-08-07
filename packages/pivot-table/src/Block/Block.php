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

use Rekalogika\PivotTable\Implementation\Table\DefaultContext;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;
use Rekalogika\PivotTable\Implementation\Table\DefaultTable;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableBody;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableFooter;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableHeader;
use Rekalogika\PivotTable\TableFramework\Cube;
use Rekalogika\PivotTable\TableFramework\CubeManager;

abstract class Block implements \Stringable
{
    private ?DefaultContext $elementContext = null;

    protected function __construct(
        private readonly BlockContext $context,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%s(level: %d)',
            static::class,
            $this->getLevel(),
        );
    }

    protected function getElementContext(): DefaultContext
    {
        return $this->elementContext ??= new DefaultContext(
            depth: $this->getLevel(),
            subtotalDepth: $this->getContext()->getSubtotalDepth(),
            generatingBlock: $this,
        );
    }

    /**
     * @return int<0,max>
     */
    final protected function getLevel(): int
    {
        return $this->context->getBlockDepth();
    }

    final protected function createBlock(Cube $cube): Block
    {
        $context = $this->getContext();
        $context = $context->pushKey();

        if ($cube->isSubtotal()) {
            $context = $context->incrementSubtotal();
        }

        if (!$context->isLeaf()) {
            if ($context->isKeyPivoted()) {
                return new PivotBlock(
                    cube: $cube,
                    parent: $this,
                    context: $context,
                );
            } else {
                return new NormalBlock(
                    cube: $cube,
                    parent: $this,
                    context: $context,
                );
            }
        } else {
            if ($context->isKeyPivoted()) {
                return new PivotLeafBlock(
                    cube: $cube,
                    parent: $this,
                    context: $context,
                );
                // } elseif (
                //     $parentNode !== null
                //     && $level > 0
                //     && \count($parentNode->getBalancedChildren(1, $level - 1)) === 1
                // ) {
                //     return new SingleNodeLeafBlock(
                //         cube: $cube,
                //         parent: $this,
                //         context: $context,
                //     );
            } else {
                return new NormalLeafBlock(
                    cube: $cube,
                    parent: $this,
                    context: $context,
                );
            }
        }
    }

    /**
     * @param list<string> $unpivotedNodes
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     */
    final public static function new(
        CubeManager $cubeManager,
        array $unpivotedNodes = [],
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Block {
        $cube = $cubeManager->createApexCube();

        $context = new BlockContext(
            apexCube: $cube,
            cubeManager: $cubeManager,
            unpivotedKeys: $unpivotedNodes,
            pivotedKeys: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );

        return new RootBlock($cube, $context);
    }

    final protected function getContext(): BlockContext
    {
        return $this->context;
    }

    abstract public function getHeaderRows(): DefaultRows;

    abstract public function getDataRows(): DefaultRows;

    final public function generateTable(): DefaultTable
    {
        $context = $this->getElementContext();

        return new DefaultTable(
            [
                new DefaultTableHeader($this->getHeaderRows(), $context),
                new DefaultTableBody($this->getDataRows(), $context),
                new DefaultTableFooter(new DefaultRows([], $context), $context),
            ],
            context: $context,
        );
    }
}
