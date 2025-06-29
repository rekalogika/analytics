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

namespace Rekalogika\Analytics\UX\PanelBundle;

use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\UX\PanelBundle\Internal\FilterFactoryLocator;

final readonly class PivotAwareQueryFactory
{
    public function __construct(
        private FilterResolver $filterFactory,
        private SummaryMetadataFactory $summaryMetadataFactory,
        private FilterFactoryLocator $filterFactoryLocator,
    ) {}

    /**
     * @param array<string,mixed> $parameters
     */
    public function createFromParameters(
        Query $query,
        array $parameters,
    ): PivotAwareQuery {
        $summaryClass = $query->getFrom();
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        return new PivotAwareQuery(
            query: $query,
            metadata: $metadata,
            parameters: $parameters,
            filterResolver: $this->filterFactory,
            filterFactoryLocator: $this->filterFactoryLocator,
        );
    }
}
