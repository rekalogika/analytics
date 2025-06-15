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

namespace Rekalogika\Analytics\Engine\SummaryManager\RefreshWorker;

use Rekalogika\Analytics\Engine\RefreshWorker\RefreshClassProperties;
use Rekalogika\Analytics\Engine\RefreshWorker\RefreshClassPropertiesResolver;

final readonly class DefaultRefreshClassPropertiesResolver implements RefreshClassPropertiesResolver
{
    /**
     * @todo properly implement this method
     */
    #[\Override]
    public function getProperties(string $class): RefreshClassProperties
    {
        return new RefreshClassProperties(
            class: $class,
            startDelay: 60,
            interval: 300,
            expectedMaximumProcessingTime: 300,
        );
    }
}
