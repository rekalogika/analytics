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

namespace Rekalogika\Analytics\Contracts\Context;

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @deprecated
 */
final readonly class GroupingStrategyContext
{
    public function __construct(
        private DimensionMetadata $dimensionMetadata,
    ) {}

    public function getDimensionMetadata(): DimensionMetadata
    {
        return $this->dimensionMetadata;
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->dimensionMetadata->getSummaryMetadata();
    }
}
