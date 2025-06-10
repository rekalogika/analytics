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

namespace Rekalogika\Analytics\Contracts\Context;

use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class SummaryContext
{
    public function __construct(
        private SummaryMetadata $summaryMetadata,
    ) {}

    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata;
    }
}
