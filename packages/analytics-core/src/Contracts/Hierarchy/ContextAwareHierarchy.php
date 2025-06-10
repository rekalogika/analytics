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

namespace Rekalogika\Analytics\Contracts\Hierarchy;

use Rekalogika\Analytics\Contracts\Context\HierarchyContext;

/**
 * If a hierarchy is context-aware, it will get an instance of HierarchyContext
 * when the hierarchy is created.
 */
interface ContextAwareHierarchy
{
    public function setContext(HierarchyContext $context): void;
}
