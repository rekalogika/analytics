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

namespace Rekalogika\Analytics\Time\Hierarchy\Trait;

use Rekalogika\Analytics\Contracts\Context\HierarchyContext;
use Rekalogika\Analytics\Contracts\Hierarchy\ContextAwareHierarchy;

/**
 * @phpstan-require-implements ContextAwareHierarchy
 */
trait ContextAwareHierarchyTrait
{
    private HierarchyContext $context;

    final public function setContext(HierarchyContext $context): void
    {
        $this->context = $context;
    }

    protected function getContext(): HierarchyContext
    {
        return $this->context;
    }
}
