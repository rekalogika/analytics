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

namespace Rekalogika\Analytics\Metadata\Summary;

use Rekalogika\Analytics\Contracts\Exception\SummaryNotFound;

/**
 * Provides the metadata of summary classes. This is the entry point for
 * everything the framework knows about a summary: its dimensions, measures,
 * partition, and how it relates to its source entity.
 */
interface SummaryMetadataFactory
{
    /**
     * Returns the metadata of the summary class.
     *
     * @param class-string $summaryClassName
     * @throws SummaryNotFound If the class does not have the `Summary`
     * attribute.
     */
    public function getSummaryMetadata(
        string $summaryClassName,
    ): SummaryMetadata;

    /**
     * Returns every summary class known to the application.
     *
     * @return list<class-string>
     */
    public function getSummaryClasses(): array;

    /**
     * Returns true if the class is a summary class.
     *
     * @param class-string $className
     */
    public function isSummary(string $className): bool;
}
