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

use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class HierarchyContext
{
    public function __construct(
        private SummaryMetadata $summaryMetadata,
        private DimensionMetadata $dimensionMetadata,
        private DimensionHierarchyMetadata $dimensionHierarchyMetadata,
    ) {}

    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata;
    }

    public function getDimensionMetadata(): DimensionMetadata
    {
        return $this->dimensionMetadata;
    }

    public function getDimensionHierarchyMetadata(): DimensionHierarchyMetadata
    {
        return $this->dimensionHierarchyMetadata;
    }
}
