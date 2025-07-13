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

namespace Rekalogika\Analytics\Engine\SummaryManager\Event;

/**
 * Event that indicates a summary entity is dirty and needs to be refreshed.
 */
final readonly class DirtySummaryEvent
{
    /**
     * @param class-string $summaryClass The class of the summary that is dirty.
     */
    public function __construct(
        private string $summaryClass,
    ) {}

    /**
     * Returns the class of the summary that is dirty.
     *
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }
}
