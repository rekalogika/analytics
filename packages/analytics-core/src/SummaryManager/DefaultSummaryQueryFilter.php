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

namespace Rekalogika\Analytics\SummaryManager;

use Rekalogika\Analytics\Query\SummaryQueryFilter;

final readonly class DefaultSummaryQueryFilter implements SummaryQueryFilter
{
    /**
     * @param list<mixed> $equalTo
     */
    public function __construct(
        private string $dimension,
        private array $equalTo,
    ) {}

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension;
    }

    #[\Override]
    public function getEqualTo(): array
    {
        return $this->equalTo;
    }
}
