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

namespace Rekalogika\Analytics\Contracts\DimensionGroup;

use Rekalogika\Analytics\Contracts\Context\DimensionGroupContext;

/**
 * If a dimension group is context-aware, it will get an instance of
 * HierarchyContext when the hierarchy is created.
 */
interface ContextAwareDimensionGroup
{
    public function setContext(DimensionGroupContext $context): void;
}
