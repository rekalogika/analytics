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

trait TimeZoneTrait
{
    abstract private function getContext(): HierarchyContext;

    private function getTimeZone(): \DateTimeZone
    {
        return $this->getContext()
            ->getDimensionMetadata()
            ->getSummaryTimeZone();
    }
}
