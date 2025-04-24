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

use Rekalogika\Analytics\Contracts\Result\Query;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

final readonly class PivotAwareQueryFactory
{
    public function __construct(
        private FilterFactory $filterFactory,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    /**
     * @param array<string,mixed> $parameters
     */
    public function createFromParameters(
        Query $query,
        array $parameters,
    ): PivotAwareQuery {
        $summaryClass = $query->getSummaryClass();
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        return new PivotAwareQuery(
            query: $query,
            metadata: $metadata,
            parameters: $parameters,
            filterFactory: $this->filterFactory,
        );
    }
}
