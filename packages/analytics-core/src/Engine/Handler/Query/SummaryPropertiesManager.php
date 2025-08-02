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

namespace Rekalogika\Analytics\Engine\Handler\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\Entity\SummaryProperties;

final readonly class SummaryPropertiesManager
{
    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $summaryClass,
    ) {}

    public function getMax(): int|string|null
    {
        $properties = $this->entityManager
            ->getRepository(SummaryProperties::class)
            ->find($this->summaryClass);

        if ($properties !== null) {
            $this->entityManager->detach($properties);
        }

        $result = $properties?->getLastId();

        if (is_numeric($result)) {
            return (int) $result;
        }

        return $result;
    }

    public function updateMax(int|string|null $max): void
    {
        $connection = $this->entityManager->getConnection();
        $metadata = $this->entityManager
            ->getClassMetadata(SummaryProperties::class);

        $tableName = $metadata->getTableName();
        $summaryClassColumn = $metadata->getColumnName('summaryClass');
        $lastIdColumn = $metadata->getColumnName('lastId');

        $sql = \sprintf(
            "
                INSERT INTO %s (
                    %s, %s
                ) VALUES (
                    :summaryClass, :lastId
                ) ON CONFLICT (%s) DO UPDATE SET %s = :lastId
            ",
            $tableName,
            $summaryClassColumn,
            $lastIdColumn,
            $summaryClassColumn,
            $lastIdColumn,
        );

        $statement = $connection->prepare($sql);
        $statement->bindValue('summaryClass', $this->summaryClass);
        $statement->bindValue('lastId', $max);
        $statement->executeStatement();
    }

    public function remove(): void
    {
        $connection = $this->entityManager->getConnection();
        $metadata = $this->entityManager
            ->getClassMetadata(SummaryProperties::class);

        $tableName = $metadata->getTableName();
        $summaryClassColumn = $metadata->getColumnName('summaryClass');

        $sql = \sprintf(
            "DELETE FROM %s WHERE %s = :summaryClass",
            $tableName,
            $summaryClassColumn,
        );

        $statement = $connection->prepare($sql);
        $statement->bindValue('summaryClass', $this->summaryClass);
        $statement->executeStatement();
    }
}
