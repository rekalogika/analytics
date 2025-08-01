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

namespace Rekalogika\PivotTable\Implementation\Table;

use Rekalogika\PivotTable\Block\Block;
use Rekalogika\PivotTable\Table\ElementContext;

final readonly class DefaultContext implements ElementContext
{
    public static function createFlat(): self
    {
        return new self(
            depth: 0,
            generatingBlock: null,
            subtotalDepth: 0,
        );
    }

    /**
     * @param int<0,max> $depth
     * @param int<0,max> $subtotalDepth
     */
    public function __construct(
        private int $depth,
        private ?Block $generatingBlock,
        private int $subtotalDepth,
    ) {}

    #[\Override]
    public function getDepth(): int
    {
        return $this->depth;
    }

    #[\Override]
    public function getGeneratingBlock(): ?Block
    {
        return $this->generatingBlock;
    }

    #[\Override]
    public function getSubtotalDepth(): int
    {
        return $this->subtotalDepth;
    }
}
