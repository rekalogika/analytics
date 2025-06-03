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

namespace Rekalogika\Analytics\Metadata;

use Rekalogika\Analytics\Exception\SummaryNotFound;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

interface SummaryMetadataFactory
{
    /**
     * @param class-string $summaryClassName
     * @throws SummaryNotFound
     */
    public function getSummaryMetadata(
        string $summaryClassName,
    ): SummaryMetadata;

    /**
     * @return list<class-string>
     */
    public function getSummaryClasses(): array;

    /**
     * @param class-string $className
     */
    public function isSummary(string $className): bool;
}
