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
use Rekalogika\PivotTable\Implementation\Table\DefaultContext;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;
use Rekalogika\PivotTable\Implementation\Table\DefaultTable;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableBody;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableFooter;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableHeader;

abstract class Block implements \Stringable
{
    public static bool $debug = true;

    private ?DefaultContext $elementContext = null;

    /**
     * @param int<0,max> $level
     */
    protected function __construct(
        private readonly int $level,
        private readonly BlockContext $context,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%s(level: %d)',
            static::class,
            $this->level,
        );
    }

    protected function getElementContext(): DefaultContext
    {
        return $this->elementContext ??= new DefaultContext(
            depth: $this->level,
            subtotalDepth: $this->getContext()->getSubtotalDepth(),
            generatingBlock: $this,
        );
    }

    /**
     * @param int<0,max> $level
     */
    private function createByType(
        TreeNodeDecorator $node,
        ?TreeNodeDecorator $parentNode,
        int $level,
        BlockContext $context,
    ): Block {
        if (!$node->isLeaf()) {
            if ($context->isPivoted($node)) {
                return new PivotBlock(
                    node: $node,
                    parentNode: $parentNode,
                    parent: $this,
                    level: $level,
                    context: $context,
                );
            } else {
                return new NormalBlock(
                    node: $node,
                    parentNode: $parentNode,
                    parent: $this,
                    level: $level,
                    context: $context,
                );
            }
        } else {
            if ($context->isPivoted($node)) {
                return new PivotLeafBlock($node, $this, $level, $context);
            } elseif (
                // ($context->getDistinctNodesOfLevel($level - 1)) === 1) {
                // $parentNode !== null &&
                // \count($node->getRoot()->getChildren($level+2)) === 1
                $parentNode !== null && count($parentNode->getBalancedChildren()) === 1
            ) {
                return new SingleNodeLeafBlock($node, $this, $level, $context);
            } else {
                return new NormalLeafBlock($node, $this, $level, $context);
            }
        }
    }

    /**
     * @return int<0,max>
     */
    final protected function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int<0,max> $level
     */
    final protected function createBlock(
        TreeNodeDecorator $node,
        ?TreeNodeDecorator $parentNode,
        int $level,
    ): Block {
        $context = $this->getContext();

        if ($node->isSubtotal()) {
            $context = $this->getContext()->incrementSubtotal();
        }

        return self::createByType(
            node: $node,
            parentNode: $parentNode,
            level: $level,
            context: $context,
        );
    }

    /**
     * @param list<string> $pivotedNodes
     * @param list<string> $skipLegends
     * @param list<string> $createSubtotals
     */
    final public static function new(
        TreeNode $node,
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Block {
        // dump(TreeNodeDebugger::debug($node));
        $decoratorRepository = new TreeNodeDecoratorRepository($node);

        $node = $decoratorRepository->decorate(
            node: $node,
            parent: null,
        );

        $context = new BlockContext(
            decoratorRepository: $decoratorRepository,
            pivotedDimensions: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );

        return new RootBlock($node, $context);
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
