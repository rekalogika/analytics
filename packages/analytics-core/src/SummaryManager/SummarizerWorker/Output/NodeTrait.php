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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Query\TreeNode;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\MeasureDescription;

trait NodeTrait
{
    private function getChild(mixed $member): ?TreeNode
    {
        /** @var mixed $currentMember */
        foreach ($this as $currentMember => $child) {
            if (
                $currentMember instanceof MeasureDescription
                && $currentMember->getMeasurePropertyName() === $member
            ) {
                return $child;
            }

            if ($currentMember === $member) {
                return $child;
            }

            if (
                $currentMember instanceof \Stringable
                && $currentMember->__toString() === $member
            ) {
                return $child;
            }
        }

        return null;
    }

    public function traverse(mixed ...$members): ?TreeNode
    {
        if ($members === []) {
            throw new \InvalidArgumentException('Invalid path');
        }

        /** @psalm-suppress MixedAssignment */
        $first = array_shift($members);

        $child = $this->getChild($first);

        if ($child === null) {
            return null;
        }

        if ($members === []) {
            return $child;
        }

        return $child->traverse(...$members);
    }
}
