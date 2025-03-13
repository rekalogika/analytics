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

namespace Rekalogika\Analytics\Bundle\UI;

use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PivotAwareSummaryQueryFactory
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * @param array<string,mixed> $parameters
     */
    public function createFromParameters(
        SummaryQuery $query,
        array $parameters,
    ): PivotAwareSummaryQuery {
        return new PivotAwareSummaryQuery(
            summaryQuery: $query,
            parameters: $parameters,
            translator: $this->translator,
        );
    }
}
