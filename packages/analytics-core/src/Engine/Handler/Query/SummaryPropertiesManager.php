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

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
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

    /**
     * Updates the latest summarized key. If the key has been read from the
     * source, `$sourceChecked` is the time just before it was read.
     */
    public function updateMax(
        int|string|null $max,
        ?\DateTimeImmutable $sourceChecked = null,
    ): void {
        $values = ['lastId' => [$max, ParameterType::STRING]];

        if ($sourceChecked !== null) {
            $values['sourceChecked'] = [$sourceChecked, Types::DATETIMETZ_IMMUTABLE];
        }

        $this->upsert($values);
    }

    public function getSourceChecked(): ?\DateTimeImmutable
    {
        /** @var array{sourceChecked:\DateTimeImmutable|null}|null */
        $result = $this->entityManager->createQueryBuilder()
            ->select('p.sourceChecked')
            ->from(SummaryProperties::class, 'p')
            ->where('p.summaryClass = :summaryClass')
            ->setParameter('summaryClass', $this->summaryClass)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['sourceChecked'] ?? null;
    }

    /**
     * Records the time just before the latest key of the source was read, and
     * found not to be greater than the latest summarized key.
     */
    public function updateSourceChecked(\DateTimeImmutable $sourceChecked): void
    {
        $this->upsert([
            'sourceChecked' => [$sourceChecked, Types::DATETIMETZ_IMMUTABLE],
        ]);
    }

    public function getScheduledRefresh(): ?\DateTimeImmutable
    {
        /** @var array{scheduledRefresh:\DateTimeImmutable|null}|null */
        $result = $this->entityManager->createQueryBuilder()
            ->select('p.scheduledRefresh')
            ->from(SummaryProperties::class, 'p')
            ->where('p.summaryClass = :summaryClass')
            ->setParameter('summaryClass', $this->summaryClass)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['scheduledRefresh'] ?? null;
    }

    public function updateScheduledRefresh(\DateTimeImmutable $scheduledRefresh): void
    {
        $this->upsert([
            'scheduledRefresh' => [$scheduledRefresh, Types::DATETIMETZ_IMMUTABLE],
        ]);
    }

    /**
     * Inserts the properties of the summary class, or updates the given
     * fields if they exist.
     *
     * @param non-empty-array<string,array{mixed,ParameterType|string}> $values field =>
     * [value, type]
     */
    private function upsert(array $values): void
    {
        $connection = $this->entityManager->getConnection();
        $metadata = $this->entityManager
            ->getClassMetadata(SummaryProperties::class);

        $tableName = $metadata->getTableName();
        $summaryClassColumn = $metadata->getColumnName('summaryClass');

        $columns = [$summaryClassColumn];
        $placeholders = [':summaryClass'];
        $updates = [];

        foreach (array_keys($values) as $field) {
            $column = $metadata->getColumnName($field);
            $columns[] = $column;
            $placeholders[] = ':' . $field;
            $updates[] = \sprintf('%s = :%s', $column, $field);
        }

        $sql = \sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO UPDATE SET %s',
            $tableName,
            implode(', ', $columns),
            implode(', ', $placeholders),
            $summaryClassColumn,
            implode(', ', $updates),
        );

        $statement = $connection->prepare($sql);
        $statement->bindValue('summaryClass', $this->summaryClass);

        /** @psalm-suppress MixedAssignment */
        foreach ($values as $field => [$value, $type]) {
            $statement->bindValue($field, $value, $type);
        }

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
