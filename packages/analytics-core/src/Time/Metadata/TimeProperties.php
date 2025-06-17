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

namespace Rekalogika\Analytics\Time\Metadata;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class TimeProperties
{
    public function __construct(
        private \DateTimeZone $sourceTimeZone = new \DateTimeZone('UTC'),
        private \DateTimeZone $summaryTimeZone = new \DateTimeZone('UTC'),
    ) {}

    public function getSourceTimeZone(): \DateTimeZone
    {
        return $this->sourceTimeZone;
    }

    public function getSummaryTimeZone(): \DateTimeZone
    {
        return $this->summaryTimeZone;
    }
}
