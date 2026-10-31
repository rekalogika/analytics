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

namespace Rekalogika\Analytics\Engine\RefreshAgent;

interface RefreshAgentStrategy
{
    /**
     * Returns the minimum age of a dirty partition that will trigger a refresh.
     * A recent dirty partition created within this minimum age will not be
     * refreshed.
     */
    public function getMinimumAge(): ?int;

    /**
     * Returns the minimum idle delay of a dirty partition before a refresh can
     * be triggered. This is to prevent too frequent refreshes. A dirty
     * partition that recently updated within this minimum idle delay will not
     * be refreshed.
     */
    public function getMinimumIdleDelay(): ?int;

    /**
     * Returns the maximum age of a dirty partition that will trigger a refresh.
     * A dirty partition older than this maximum age will be refreshed, ignoring
     * the minimum idle delay. This prevents a partition that is continuously
     * receiving changes from being postponed indefinitely.
     */
    public function getMaximumAge(): ?int;

    /**
     * Returns the cooldown period of the upper-level partitions. The dirty
     * upper-level partitions are caused by the refresh of the partitions below
     * them, and are refreshed immediately, unless they have been refreshed
     * within this cooldown period. In that case, the refresh is postponed
     * until the cooldown period ends. Null means the upper-level partitions
     * are always refreshed immediately.
     */
    public function getUpperPartitionCooldown(): ?int;
}
