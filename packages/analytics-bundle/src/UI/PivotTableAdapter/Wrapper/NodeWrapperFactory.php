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

namespace Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper;

use Rekalogika\Analytics\Contracts\Result\TreeNode;

final class NodeWrapperFactory
{
    /**
     * @var array<string,NodeLabel>
     */
    private array $labelCache = [];

    /**
     * @var array<string,NodeMember>
     */
    private array $memberCache = [];


    /**
     * @var array<string,NodeValue>
     */
    private array $valueCache = [];

    private function getHash(TreeNode $treeNode): string
    {
        /** @psalm-suppress MixedAssignment */
        $item = $treeNode->getRawMember();

        if (\is_object($item)) {
            $objectSeed = (string) spl_object_id($item);
        } else {
            $objectSeed = serialize($item);
        }

        return hash('xxh128', $objectSeed . $treeNode->getKey());
    }

    public function getLabel(TreeNode $treeNode): NodeLabel
    {
        $hash = $this->getHash($treeNode);

        return $this->labelCache[$hash] ??= new NodeLabel($treeNode);
    }

    public function getMember(TreeNode $treeNode): NodeMember
    {
        $hash = $this->getHash($treeNode);

        return $this->memberCache[$hash] ??= new NodeMember($treeNode);
    }

    public function getValue(TreeNode $treeNode): NodeValue
    {
        $hash = $this->getHash($treeNode);

        return $this->valueCache[$hash] ??= new NodeValue($treeNode);
    }
}
