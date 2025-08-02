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

namespace Rekalogika\Analytics\Engine\SummaryRefresher;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\Handler\PartitionHandler;
use Rekalogika\Analytics\Engine\SummaryRefresher\Query\DeleteExistingSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\Query\InsertIntoSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\Query\RollUpSourceToSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\Query\RollUpSummaryToSummaryGroupAllStrategyQuery;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final class SqlFactory
{
    private readonly ClassMetadataWrapper $doctrineClassMetadata;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $summaryMetadata,
        private readonly PartitionHandler $partitionManager,
    ) {
        $this->doctrineClassMetadata = new ClassMetadataWrapper(
            manager: $this->entityManager,
            class: $this->summaryMetadata->getSummaryClass(),
        );
    }

    //
    // insert into summary
    //

    private ?string $insertInto = null;

    private function getInsertIntoSummaryQuery(): string
    {
        if ($this->insertInto !== null) {
            return $this->insertInto;
        }

        $query = new InsertIntoSummaryQuery(
            doctrineClassMetadata: $this->doctrineClassMetadata,
            summaryMetadata: $this->summaryMetadata,
        );

        return $this->insertInto = $query->getSQL();
    }

    //
    // delete
    //

    private ?SummaryRefresherQuery $deleteExistingSummaryQuery = null;

    public function getDeleteExistingSummaryQuery(): SummaryRefresherQuery
    {
        return $this->deleteExistingSummaryQuery ??=
            new DeleteExistingSummaryQuery(
                entityManager: $this->entityManager,
                summaryMetadata: $this->summaryMetadata,
            );
    }

    //
    // rollup source to summary
    //

    private ?SummaryRefresherQuery $rollUpSourceToSummaryQuery = null;

    public function getRollUpSourceToSummaryQuery(): SummaryRefresherQuery
    {
        return $this->rollUpSourceToSummaryQuery ??=
            new RollUpSourceToSummaryQuery(
                entityManager: $this->entityManager,
                partitionManager: $this->partitionManager,
                summaryMetadata: $this->summaryMetadata,
                insertSql: $this->getInsertIntoSummaryQuery(),
            );
    }

    //
    // rollup summary to summary
    //

    private ?SummaryRefresherQuery $rollUpSummaryToSummaryQuery = null;

    public function getRollUpSummaryToSummaryQuery(): SummaryRefresherQuery
    {
        return $this->rollUpSummaryToSummaryQuery ??=
            new RollUpSummaryToSummaryGroupAllStrategyQuery(
                entityManager: $this->entityManager,
                metadata: $this->summaryMetadata,
                insertSql: $this->getInsertIntoSummaryQuery(),
            );
    }
}
