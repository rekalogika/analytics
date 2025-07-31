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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\GroupingField;

final readonly class DefaultRow implements Row
{
    public function __construct(
        private DefaultTuple $tuple,
        private DefaultMeasures $measures,
        private ?GroupingField $groupings,
    ) {}

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->measures;
    }

    #[\Override]
    public function getTuple(): DefaultTuple
    {
        return $this->tuple;
    }

    public function getGroupings(): ?GroupingField
    {
        return $this->groupings;
    }

    public function isGrouping(): bool
    {
        return $this->groupings?->isSubtotal() ?? false;
    }

    public function getSignature(): string
    {
        return $this->tuple->getSignature();
    }
}
