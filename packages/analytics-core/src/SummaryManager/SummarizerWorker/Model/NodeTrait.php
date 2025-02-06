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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

use Rekalogika\Analytics\Query\ResultNode;

trait NodeTrait
{
    private function getChild(mixed $item): ?ResultNode
    {
        /** @var mixed $currentItem */
        foreach ($this as $currentItem => $child) {
            if (
                $currentItem instanceof MeasureDescription
                && $currentItem->getMeasurePropertyName() === $item
            ) {
                return $child;
            }

            if ($currentItem === $item) {
                return $child;
            }

            if (
                $currentItem instanceof \Stringable
                && $currentItem->__toString() === $item
            ) {
                return $child;
            }
        }

        return null;
    }

    public function traverse(mixed ...$items): ?ResultNode
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Invalid path');
        }

        /** @psalm-suppress MixedAssignment */
        $first = array_shift($items);

        $child = $this->getChild($first);

        if ($child === null) {
            return null;
        }

        if ($items === []) {
            return $child;
        }

        return $child->traverse(...$items);
    }
}
