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

namespace Rekalogika\Analytics\PivotTable\Util;

use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\PivotTable\Model\Label;
use Rekalogika\Analytics\PivotTable\Model\Member;

/**
 * Identity map for objects that represent properties of a TreeNode. It needs
 * an identity map because pivot-table depends on identity comparison.
 */
final class PropertyMap
{
    /**
     * @var array<string,Label>
     */
    private array $labelMap = [];

    /**
     * @var array<string,Member>
     */
    private array $memberMap = [];

    public function __construct() {}

    private function getHash(TreeNode $treeNode): string
    {
        /** @psalm-suppress MixedAssignment */
        $item = $treeNode->getRawMember();

        if (\is_object($item)) {
            $objectSeed = (string) spl_object_id($item);
        } else {
            $objectSeed = serialize($item);
        }

        return hash('xxh128', $objectSeed . $treeNode->getName());
    }

    public function getLabel(TreeNode $treeNode): Label
    {
        $hash = $this->getHash($treeNode);

        return $this->labelMap[$hash] ??= new Label($treeNode);
    }

    public function getMember(TreeNode $treeNode): Member
    {
        $hash = $this->getHash($treeNode);

        return $this->memberMap[$hash] ??= new Member($treeNode);
    }
}
