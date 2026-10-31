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

namespace Rekalogika\Analytics\Engine\Util;

use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStrategy;

final readonly class RefreshAgentStrategyUtil
{
    private function __construct() {}

    /**
     * Gets the time a dirty partition will be ready to be refreshed according
     * to the strategy, assuming no more changes are made to the partition.
     *
     * @param \DateTimeImmutable $earliest The time of the earliest dirty flag
     * @param \DateTimeImmutable $latest The time of the latest dirty flag
     */
    public static function getRefreshTime(
        RefreshAgentStrategy $refreshAgentStrategy,
        \DateTimeImmutable $earliest,
        \DateTimeImmutable $latest,
    ): \DateTimeImmutable {
        $minimumAge = $refreshAgentStrategy->getMinimumAge();
        $minimumIdleDelay = $refreshAgentStrategy->getMinimumIdleDelay();
        $maximumAge = $refreshAgentStrategy->getMaximumAge();

        $refreshTime = $earliest;

        if ($minimumIdleDelay !== null) {
            $refreshTime = $latest->modify(\sprintf('+%d seconds', $minimumIdleDelay));

            if ($maximumAge !== null) {
                $refreshTime = min(
                    $refreshTime,
                    $earliest->modify(\sprintf('+%d seconds', $maximumAge)),
                );
            }
        }

        if ($minimumAge !== null) {
            $refreshTime = max(
                $refreshTime,
                $earliest->modify(\sprintf('+%d seconds', $minimumAge)),
            );
        }

        return $refreshTime;
    }
}
