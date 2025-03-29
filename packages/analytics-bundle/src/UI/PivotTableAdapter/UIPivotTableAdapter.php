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

namespace Rekalogika\Analytics\Bundle\UI\PivotTableAdapter;

use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeWrapperFactory;
use Rekalogika\Analytics\Contracts\Result\Tree;
use Rekalogika\PivotTable\Contracts\BranchNode;

final readonly class UIPivotTableAdapter implements BranchNode
{
    private NodeWrapperFactory $nodeWrapperFactory;

    public function __construct(
        private Tree $result,
    ) {
        $this->nodeWrapperFactory = new NodeWrapperFactory();
    }

    #[\Override]
    public function getKey(): string
    {
        return '';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return null;
    }

    #[\Override]
    public function getItem(): mixed
    {
        return null;
    }

    #[\Override]
    public function getChildren(): iterable
    {
        foreach ($this->result as $item) {
            if ($item->isNull()) {
                continue;
            }

            if (\count($item) > 0) {
                yield new UIPivotTableBranch($item, $this->nodeWrapperFactory);
            } else {
                yield new UIPivotTableLeaf($item, $this->nodeWrapperFactory);
            }
        }
    }
}
