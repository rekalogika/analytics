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

namespace Rekalogika\Analytics\Engine\SummaryManager;

use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContextFactory;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class DefaultSummaryManager implements SummaryManager
{
    private ResultContextFactory $resultContextFactory;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $metadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private SummaryRefresherFactory $refresherFactory,
        private SourceEntitiesFactory $sourceEntitiesFactory,
        private int $queryResultLimit,
        private int $fillingNodesLimit,
    ) {
        $this->resultContextFactory = new ResultContextFactory(
            summaryMetadataFactory: $this->metadataFactory,
            managerRegistry: $this->managerRegistry,
            propertyAccessor: $this->propertyAccessor,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
            nodesLimit: $this->fillingNodesLimit,
            queryResultLimit: $this->queryResultLimit,
        );
    }

    #[\Override]
    public function refresh(
        string $class,
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void {
        $this->refresherFactory
            ->createSummaryRefresher($class)
            ->manualRefresh(
                start: $start,
                end: $end,
                batchSize: $batchSize,
                resumeId: $resumeId,
            );
    }

    #[\Override]
    public function createQuery(
        ?int $queryResultLimit = null,
        ?int $fillingNodesLimit = null,
    ): DefaultQuery {
        $resultContextFactory = $this->resultContextFactory;

        if ($queryResultLimit !== null) {
            $resultContextFactory = $resultContextFactory
                ->withQueryResultLimit($queryResultLimit);
        }

        if ($fillingNodesLimit !== null) {
            $resultContextFactory = $resultContextFactory
                ->withNodesLimit($fillingNodesLimit);
        }

        return new DefaultQuery(
            summaryMetadataFactory: $this->metadataFactory,
            resultContextFactory: $resultContextFactory,
        );
    }
}
