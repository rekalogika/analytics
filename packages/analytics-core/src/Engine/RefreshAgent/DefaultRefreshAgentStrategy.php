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

final readonly class DefaultRefreshAgentStrategy implements RefreshAgentStrategy
{
    public function __construct(
        private ?int $minimumAge = 600, // 10 minutes
        private ?int $minimumIdleDelay = 300, // 5 minutes
        private ?int $maximumAge = 21600, // 6 hours
    ) {}

    #[\Override]
    public function getMinimumAge(): ?int
    {
        return $this->minimumAge;
    }

    #[\Override]
    public function getMinimumIdleDelay(): ?int
    {
        return $this->minimumIdleDelay;
    }

    #[\Override]
    public function getMaximumAge(): ?int
    {
        return $this->maximumAge;
    }
}
