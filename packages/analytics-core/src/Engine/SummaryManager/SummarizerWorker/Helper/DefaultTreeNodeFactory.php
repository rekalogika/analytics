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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Contracts\Exception\InterpolationOverflowException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTree;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTuple;

final class DefaultTreeNodeFactory
{
    private int $nodesCount = 0;

    public function __construct(
        private readonly int $nodesLimit,
        private readonly TreeContext $context,
    ) {}

    /**
     * @param list<string> $descendantdimensionNames
     * @param list<string> $measureNames
     */
    public function createNode(
        DefaultTuple $tuple,
        array $descendantdimensionNames,
        array $measureNames,
    ): DefaultTree {
        if ($this->nodesCount >= $this->nodesLimit) {
            throw new InterpolationOverflowException($this->nodesLimit);
        }

        $this->nodesCount++;

        return new DefaultTree(
            tuple: $tuple,
            descendantdimensionNames: $descendantdimensionNames,
            measureNames: $measureNames,
            rootLabel: null,
            context: $this->context,
        );
    }
}
