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
        return new self(0, null);
    }

    /**
     * @param int<0,max> $depth
     */
    public function __construct(
        private int $depth,
        private ?Block $generatingBlock,
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
}
