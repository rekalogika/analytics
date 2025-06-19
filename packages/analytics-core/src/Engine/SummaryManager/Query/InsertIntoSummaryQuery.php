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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query;

use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

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
        $fieldName = $partitionMetadata->getName();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

        $columns[] = $this->doctrineClassMetadata
            ->getSQLFieldName(\sprintf('%s.%s', $fieldName, $partitionKeyProperty));

        $columns[] = $this->doctrineClassMetadata
            ->getSQLFieldName(\sprintf('%s.%s', $fieldName, $partitionLevelProperty));

        // add dimension columns

        foreach ($this->summaryMetadata->getLeafDimensions() as $dimensionMetadata) {
            $property = $dimensionMetadata->getName();
            $columns[] = $this->doctrineClassMetadata->getSQLFieldName($property);
        }

        // add measure columns

        foreach ($this->summaryMetadata->getMeasures() as $property => $measureMetadata) {
            if ($measureMetadata->isVirtual()) {
                continue;
            }

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
