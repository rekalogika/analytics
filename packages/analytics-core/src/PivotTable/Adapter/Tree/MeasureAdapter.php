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

namespace Rekalogika\Analytics\PivotTable\Adapter\Tree;

use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\PivotTable\Contracts\Tree\LeafNode;

final readonly class MeasureAdapter implements LeafNode
{
    public static function adapt(Measure $measure): self
    {
        return new self($measure);
    }

    private function __construct(
        private Measure $measure,
    ) {}

    #[\Override]
    public function getValue(): mixed
    {
        return $this->measure->getValue();
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->measure->getName();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->measure->getLabel();
    }

    /**
     * @todo maybe replace with a MeasureMember?
     */
    #[\Override]
    public function getItem(): mixed
    {
        return 'subtotal';
    }
}
