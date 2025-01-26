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

namespace Rekalogika\Analytics;

use Rekalogika\Analytics\Metadata\DimensionHierarchyMetadata;
use Rekalogika\Analytics\Metadata\DimensionLevelMetadata;
use Rekalogika\Analytics\Metadata\DimensionMetadata;
use Rekalogika\Analytics\Metadata\DimensionPathMetadata;
use Rekalogika\Analytics\Metadata\DimensionPropertyMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\SummaryManager\Query\QueryContext;

final readonly class DimensionValueResolverContext
{
    public function __construct(
        private QueryContext $queryContext,
        private DimensionPropertyMetadata $propertyMetadata,
    ) {}

    public function resolvePath(string $path): string
    {
        return $this->queryContext->resolvePath($path);
    }

    public function getPropertyMetadata(): DimensionPropertyMetadata
    {
        return $this->propertyMetadata;
    }

    public function getLevelMetadata(): DimensionLevelMetadata
    {
        return $this->propertyMetadata->getLevelMetadata();
    }

    public function getPathMetadata(): DimensionPathMetadata
    {
        return $this->getLevelMetadata()->getPathMetadata();
    }

    public function getHierarchyMetadata(): DimensionHierarchyMetadata
    {
        return $this->getPathMetadata()->getHierarchyMetadata();
    }

    public function getDimensionMetadata(): DimensionMetadata
    {
        return $this->getHierarchyMetadata()->getDimensionMetadata();
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->getDimensionMetadata()->getSummaryMetadata();
    }
}
