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

namespace Rekalogika\Analytics\Engine\Doctrine\EventListener;

use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Common\Exception\SummaryNotFound;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final readonly class SummaryPostGenerateSchemaTableListener
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private ManagerRegistry $managerRegistry,
    ) {}

    /**
     * Automatically add indexes to summary table
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args): void
    {
        $class = $args->getClassMetadata()->getName();
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        $classMetadata = new ClassMetadataWrapper($entityManager, $class);
        $table = $args->getClassTable();

        try {
            $summaryMetadata = $this->summaryMetadataFactory
                ->getSummaryMetadata($classMetadata->getClass());
        } catch (SummaryNotFound) {
            return;
        }

        $partitionMetadata = $summaryMetadata->getPartition();

        $partitionLevelColumnName = $classMetadata
            ->getSQLFieldName(\sprintf(
                '%s.%s',
                $partitionMetadata->getName(),
                $partitionMetadata->getPartitionLevelProperty(),
            ));

        $partitionKeyColumnName = $classMetadata
            ->getSQLFieldName(\sprintf(
                '%s.%s',
                $partitionMetadata->getName(),
                $partitionMetadata->getPartitionKeyProperty(),
            ));

        $groupingsColumnName = $classMetadata
            ->getSQLFieldName($summaryMetadata->getGroupingsProperty());

        $table->addIndex([
            $partitionLevelColumnName,
            $partitionKeyColumnName,
        ]);

        $table->addIndex([
            $groupingsColumnName,
            $partitionLevelColumnName,
            $partitionKeyColumnName,
        ]);
    }
}
