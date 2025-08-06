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

    /**
     * @param int<1,max> $levelIncrement
     */
    final protected function createBlock(
        TreeNodeDecorator $node,
        ?TreeNodeDecorator $parentNode,
        int $levelIncrement,
        string $key,
    ): Block {
        $context = $this->getContext();
        $level = $context->getBlockDepth();

        $context = $context
            ->incrementBlockDepth($levelIncrement)
            ->appendKey($key);

        if ($node->isSubtotal()) {
            $context = $context->incrementSubtotal();
        }

        if (!$node->isLeaf()) {
            if ($context->isKeyPivoted($node->getKey())) {
                return new PivotBlock(
                    node: $node,
                    parentNode: $parentNode,
                    parent: $this,
                    context: $context,
                );
            } else {
                return new NormalBlock(
                    node: $node,
                    parentNode: $parentNode,
                    parent: $this,
                    context: $context,
                );
            }
        } else {
            if ($context->isKeyPivoted($node->getKey())) {
                return new PivotLeafBlock(
                    node: $node,
                    parent: $this,
                    context: $context,
                );
            } elseif (
                $parentNode !== null
                && $level > 0
                && \count($parentNode->getBalancedChildren(1, $level - 1)) === 1
            ) {
                return new SingleNodeLeafBlock(
                    node: $node,
                    parent: $this,
                    context: $context,
                );
            } else {
                return new NormalLeafBlock(
                    node: $node,
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
        TreeNode $node,
        array $unpivotedNodes = [],
        array $pivotedNodes = [],
        array $skipLegends = ['@values'],
        array $createSubtotals = [],
    ): Block {
        $repository = new TreeNodeDecoratorRepository();
        $rootNode = $repository->decorate($node);

        $context = new BlockContext(
            rootNode: $rootNode,
            repository: $repository,
            unpivotedKeys: $unpivotedNodes,
            pivotedKeys: $pivotedNodes,
            skipLegends: $skipLegends,
            createSubtotals: $createSubtotals,
        );

        return new RootBlock($rootNode, $context);
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
