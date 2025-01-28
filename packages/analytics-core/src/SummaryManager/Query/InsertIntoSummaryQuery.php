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

namespace Rekalogika\Analytics\SummaryManager\Query;

use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\SummaryMetadata;

final readonly class InsertIntoSummaryQuery
{
    public function __construct(
        private ClassMetadataWrapper $doctrineClassMetadata,
        private SummaryMetadata $summaryMetadata,
    ) {}

    public function getSQL(): string
    {
        $columns = [];

        // add primary key

        $columns[] = $this->doctrineClassMetadata->getSQLIdentifierFieldName();

        // add partition columns

        $partitionMetadata = $this->summaryMetadata->getPartition();
        $fieldName = $partitionMetadata->getSummaryProperty();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

        $columns[] = $this->doctrineClassMetadata
            ->getSQLFieldName(\sprintf('%s.%s', $fieldName, $partitionKeyProperty));

        $columns[] = $this->doctrineClassMetadata
            ->getSQLFieldName(\sprintf('%s.%s', $fieldName, $partitionLevelProperty));

        // add dimension columns

        foreach ($this->summaryMetadata->getDimensionMetadatas() as $dimensionMetadata) {
            $property = $dimensionMetadata->getSummaryProperty();
            $hierarchyMetadata = $dimensionMetadata->getHierarchy();

            // if not hierarchical

            if ($hierarchyMetadata === null) {
                $columns[] = $this->doctrineClassMetadata->getSQLFieldName($property);

                continue;
            }

            // if hierarchical

            foreach ($hierarchyMetadata->getProperties() as $property) {
                $p = \sprintf(
                    '%s.%s',
                    $dimensionMetadata->getSummaryProperty(),
                    $property->getName(),
                );

                $columns[] = $this->doctrineClassMetadata->getSQLFieldName($p);
            }
        }

        // add measure columns

        foreach (array_keys($this->summaryMetadata->getMeasureMetadatas()) as $property) {
            $columns[] = $this->doctrineClassMetadata->getSQLFieldName($property);
        }

        // add groupings column

        $columns[] = $this->doctrineClassMetadata
            ->getSQLFieldName($this->summaryMetadata->getGroupingsProperty());

        return \sprintf(
            'INSERT INTO %s (%s)',
            $this->doctrineClassMetadata->getSQLTableName(),
            implode(', ', $columns),
        );
    }
}
