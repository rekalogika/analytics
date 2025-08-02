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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\PartitionHandler;
use Rekalogika\Analytics\Engine\SummaryManager\Query\DeleteExistingSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\InsertIntoSummaryQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\RollUpSourceToSummaryPerSourceQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\RollUpSummaryToSummaryGroupAllStrategyQuery;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SummaryEntityQuery;
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

    private ?SummaryEntityQuery $deleteExistingSummaryQuery = null;

    public function getDeleteExistingSummaryQuery(): SummaryEntityQuery
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

    private ?SummaryEntityQuery $rollUpSourceToSummaryQuery = null;

    public function getRollUpSourceToSummaryQuery(): SummaryEntityQuery
    {
        return $this->rollUpSourceToSummaryQuery ??=
            new RollUpSourceToSummaryPerSourceQuery(
                entityManager: $this->entityManager,
                partitionManager: $this->partitionManager,
                summaryMetadata: $this->summaryMetadata,
                insertSql: $this->getInsertIntoSummaryQuery(),
            );
    }

    //
    // rollup summary to summary
    //

    private ?SummaryEntityQuery $rollUpSummaryToSummaryQuery = null;

    public function getRollUpSummaryToSummaryQuery(): SummaryEntityQuery
    {
        return $this->rollUpSummaryToSummaryQuery ??=
            new RollUpSummaryToSummaryGroupAllStrategyQuery(
                entityManager: $this->entityManager,
                metadata: $this->summaryMetadata,
                insertSql: $this->getInsertIntoSummaryQuery(),
            );
    }
}
