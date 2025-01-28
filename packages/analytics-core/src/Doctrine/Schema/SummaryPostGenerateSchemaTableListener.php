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

namespace Rekalogika\Analytics\Doctrine\Schema;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Exception\SummaryNotFound;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

#[AsDoctrineListener(ToolEvents::postGenerateSchemaTable)]
class SummaryPostGenerateSchemaTableListener
{
    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    /**
     * Automatically add indexes to summary table
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args): void
    {
        $classMetadata = new ClassMetadataWrapper($args->getClassMetadata());
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
                $partitionMetadata->getSummaryProperty(),
                $partitionMetadata->getPartitionLevelProperty(),
            ));

        $partitionKeyColumnName = $classMetadata
            ->getSQLFieldName(\sprintf(
                '%s.%s',
                $partitionMetadata->getSummaryProperty(),
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
