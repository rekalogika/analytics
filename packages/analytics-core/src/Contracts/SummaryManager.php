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

namespace Rekalogika\Analytics\Contracts;

use Rekalogika\Analytics\SummaryManager\SummaryQuery;

/**
 * @template T of object
 */
interface SummaryManager
{
    public function updateBySourceRange(
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void;

    public function createQuery(): SummaryQuery;
}
