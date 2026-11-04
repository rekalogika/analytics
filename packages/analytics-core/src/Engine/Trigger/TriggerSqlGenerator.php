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

namespace Rekalogika\Analytics\Engine\Trigger;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Infrastructure\SourceQueryBuilderFactory;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Source\AssociationDependency;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

/**
 * Generates PostgreSQL triggers that create the dirty flags of the summaries
 * when their source tables are modified, by any means: the ORM, DQL `UPDATE`
 * and `DELETE`, native SQL, migrations, or database cascades.
 *
 * Each table gets one function and up to three statement-level triggers,
 * using transition tables, so the cost is one `INSERT … SELECT` per summary
 * per statement, regardless of the number of rows:
 *
 * - On the source table of a summary: `INSERT`, `DELETE`, and `UPDATE` of
 *   the columns the summary reads. The partitions of the rows before and
 *   after the update are flagged.
 * - On the table of an entity the summary reads through an association:
 *   `UPDATE` of the columns the summary reads. The partitions of the source
 *   rows referring to the modified rows are flagged. A deletion of a related
 *   row affects the source rows only through a foreign key action, which
 *   fires the triggers of the source table.
 *
 * The triggers only create the dirty flags. They do not dispatch the
 * refresh agent, the sweeper does.
 *
 * Not supported: class table inheritance, composite identifiers, and
 * `TRUNCATE`.
 */
final readonly class TriggerSqlGenerator
{
    public const PREFIX = 'rekalogika_dirty_';

    private const NEW_ROWS = 'rekalogika_new_rows';
    private const OLD_ROWS = 'rekalogika_old_rows';

    private string $timezone;

    /**
     * @param string|null $timezone The time zone of the creation time of the
     * dirty flags. It must be the default time zone of PHP, the time zone in
     * which Doctrine writes the creation time of the flags created by the
     * application. Defaults to the current default time zone of PHP.
     */
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        private SourceMetadataFactory $sourceMetadataFactory,
        private ManagerRegistry $managerRegistry,
        ?string $timezone = null,
    ) {
        $this->timezone = $timezone ?? date_default_timezone_get();
    }

    /**
     * Generates the statements creating the functions and the triggers, for
     * each entity manager having a summary. The statements can be run again,
     * they replace the functions and the triggers.
     *
     * @return array<string,list<string>> The statements, keyed by the name
     * of the entity manager.
     */
    public function getCreateStatements(): array
    {
        $result = [];

        foreach ($this->getSummariesByManager() as $managerName => [$entityManager, $summaries]) {
            $result[$managerName] = $this->getCreateStatementsOfManager($entityManager, $summaries);
        }

        return $result;
    }

    /**
     * Generates the statements dropping the functions and the triggers
     * created by this generator that exist in the database, for each entity
     * manager having a summary. Includes the ones of tables no longer used by
     * any summary.
     *
     * @return array<string,list<string>> The statements, keyed by the name
     * of the entity manager.
     */
    public function getDropStatements(): array
    {
        $result = [];

        foreach ($this->getSummariesByManager() as $managerName => [$entityManager]) {
            $result[$managerName] = $this->getDropStatementsOfConnection($entityManager->getConnection());
        }

        return $result;
    }

    /**
     * The connection of each entity manager having a summary.
     *
     * @return array<string,Connection>
     */
    public function getConnections(): array
    {
        return array_map(
            static fn(array $managerAndSummaries): Connection => $managerAndSummaries[0]->getConnection(),
            $this->getSummariesByManager(),
        );
    }

    /**
     * @return list<string>
     */
    private function getDropStatementsOfConnection(Connection $connection): array
    {
        $platform = $this->getPlatform($connection);
        $pattern = $platform->quoteStringLiteral(str_replace('_', '\_', self::PREFIX) . '%');

        /** @var list<array{tgname:string,tbl:string}> */
        $triggers = $connection->fetchAllAssociative(\sprintf(
            'SELECT t.tgname, t.tgrelid::regclass::text AS tbl FROM pg_trigger t WHERE NOT t.tgisinternal AND t.tgname LIKE %s ORDER BY 2, 1',
            $pattern,
        ));

        /** @var list<string> */
        $functions = $connection->fetchFirstColumn(\sprintf(
            'SELECT p.oid::regprocedure::text FROM pg_proc p WHERE p.proname LIKE %s ORDER BY 1',
            $pattern,
        ));

        $statements = [];

        foreach ($triggers as $trigger) {
            $statements[] = \sprintf(
                'DROP TRIGGER IF EXISTS %s ON %s',
                $platform->quoteSingleIdentifier($trigger['tgname']),
                $trigger['tbl'],
            );
        }

        foreach ($functions as $function) {
            $statements[] = \sprintf('DROP FUNCTION IF EXISTS %s', $function);
        }

        return $statements;
    }

    /**
     * @return array<string,array{EntityManagerInterface,list<SummaryMetadata>}>
     */
    private function getSummariesByManager(): array
    {
        /** @var array<string,EntityManagerInterface> */
        $managers = [];

        /** @var array<string,list<SummaryMetadata>> */
        $summaries = [];

        foreach ($this->summaryMetadataFactory->getSummaryClasses() as $summaryClass) {
            $entityManager = $this->managerRegistry->getManagerForClass($summaryClass);

            if (!$entityManager instanceof EntityManagerInterface) {
                continue;
            }

            $managerName = $this->getManagerName($entityManager);

            $managers[$managerName] = $entityManager;
            $summaries[$managerName][] = $this->summaryMetadataFactory
                ->getSummaryMetadata($summaryClass);
        }

        ksort($managers);

        $result = [];

        foreach ($managers as $managerName => $entityManager) {
            $result[$managerName] = [$entityManager, $summaries[$managerName] ?? []];
        }

        return $result;
    }

    private function getManagerName(EntityManagerInterface $entityManager): string
    {
        foreach ($this->managerRegistry->getManagers() as $name => $manager) {
            if ($manager === $entityManager) {
                return $name;
            }
        }

        throw new LogicException('The entity manager is not in the registry.');
    }

    private function getPlatform(Connection $connection): PostgreSQLPlatform
    {
        $platform = $connection->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new LogicException(\sprintf(
                'Triggers are only supported on PostgreSQL, got "%s".',
                $platform::class,
            ));
        }

        return $platform;
    }

    /**
     * @param list<SummaryMetadata> $summaries
     * @return list<string>
     */
    private function getCreateStatementsOfManager(
        EntityManagerInterface $entityManager,
        array $summaries,
    ): array {
        $platform = $this->getPlatform($entityManager->getConnection());

        /**
         * For each table, the statements of each operation.
         *
         * @var array<string,array<'INSERT'|'UPDATE'|'DELETE',list<string>>>
         */
        $tables = [];

        /**
         * The source tables, which get the insert and delete triggers.
         *
         * @var array<string,true>
         */
        $sourceTables = [];

        foreach ($summaries as $summaryMetadata) {
            foreach ($this->getSourceTableStatements($entityManager, $platform, $summaryMetadata) as [$table, $operation, $statement]) {
                $sourceTables[$table] = true;
                $tables[$table][$operation][] = $statement;
            }

            foreach ($this->getRelatedTableStatements($entityManager, $platform, $summaryMetadata) as [$table, $statement]) {
                $tables[$table]['UPDATE'][] = $statement;
            }
        }

        ksort($tables);

        $statements = [];

        foreach ($tables as $table => $operations) {
            $function = $this->getFunctionName($table);
            $statements[] = $this->getFunctionStatement($function, $operations);
            $isSource = isset($sourceTables[$table]);

            $triggers = [
                'insert' => $isSource ? \sprintf('AFTER INSERT ON %s REFERENCING NEW TABLE AS %s', $table, self::NEW_ROWS) : null,
                'update' => \sprintf('AFTER UPDATE ON %s REFERENCING OLD TABLE AS %s NEW TABLE AS %s', $table, self::OLD_ROWS, self::NEW_ROWS),
                'delete' => $isSource ? \sprintf('AFTER DELETE ON %s REFERENCING OLD TABLE AS %s', $table, self::OLD_ROWS) : null,
            ];

            foreach ($triggers as $name => $definition) {
                $trigger = $platform->quoteSingleIdentifier(self::PREFIX . $name);

                $statements[] = \sprintf('DROP TRIGGER IF EXISTS %s ON %s', $trigger, $table);

                if ($definition !== null) {
                    $statements[] = \sprintf(
                        'CREATE TRIGGER %s %s FOR EACH STATEMENT EXECUTE FUNCTION %s()',
                        $trigger,
                        $definition,
                        $function,
                    );
                }
            }
        }

        return $statements;
    }

    /**
     * @param array<'INSERT'|'UPDATE'|'DELETE',list<string>> $operations
     */
    private function getFunctionStatement(string $function, array $operations): string
    {
        $branches = [];

        foreach (['INSERT', 'UPDATE', 'DELETE'] as $operation) {
            $operationStatements = $operations[$operation] ?? [];

            if ($operationStatements === []) {
                continue;
            }

            $branches[] = \sprintf(
                "%s TG_OP = '%s' THEN\n%s",
                $branches === [] ? 'IF' : 'ELSIF',
                $operation,
                implode("\n", array_map(
                    static fn(string $statement): string => '        ' . $statement . ';',
                    $operationStatements,
                )),
            );
        }

        $body = $branches === []
            ? ''
            : '    ' . implode("\n    ", $branches) . "\n    END IF;\n";

        return \sprintf(
            "CREATE OR REPLACE FUNCTION %s() RETURNS trigger LANGUAGE plpgsql AS \$rekalogika\$\nBEGIN\n%s    RETURN NULL;\nEND\n\$rekalogika\$",
            $function,
            $body,
        );
    }

    /**
     * The function name is derived from the table name, and is shortened to
     * the maximum length of an identifier if needed.
     */
    private function getFunctionName(string $table): string
    {
        $name = self::PREFIX . trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($table)), '_');

        if (\strlen($name) > 63) {
            $name = substr($name, 0, 54) . '_' . substr(md5($table), 0, 8);
        }

        return $name;
    }

    /**
     * @return iterable<array{string,'INSERT'|'UPDATE'|'DELETE',string}> The
     * table, the operation and the statement.
     */
    private function getSourceTableStatements(
        EntityManagerInterface $entityManager,
        AbstractPlatform $platform,
        SummaryMetadata $summaryMetadata,
    ): iterable {
        $sourceClass = $summaryMetadata->getSourceClass();
        $classMetadata = $this->getClassMetadata($entityManager, $sourceClass);
        $table = $this->getTableName($entityManager, $platform, $classMetadata);

        // the columns of the properties the summary reads

        $properties = [];

        foreach ($this->sourceMetadataFactory->getSourceMetadata($sourceClass)->getPropertyToSummaryClasses() as $property => $summaryClasses) {
            if (\in_array($summaryMetadata->getSummaryClass(), $summaryClasses, true)) {
                $properties[] = $property;
            }
        }

        $columns = $this->getColumns($entityManager, $platform, $classMetadata, $properties);

        // a change of the type of the entity

        // the mapping is an array in ORM 2, an object with public properties
        // in ORM 3

        /** @psalm-suppress MixedAssignment */
        $discriminatorColumn = ((array) $classMetadata->discriminatorColumn)['name'] ?? null;

        if (\is_string($discriminatorColumn)) {
            $columns[] = $discriminatorColumn;
        }

        $columns = array_values(array_unique($columns));

        [$select, $alias, $tail] = $this->getPartitionSelect($entityManager, $summaryMetadata);

        $identifiers = $this->getIdentifierColumns($entityManager, $platform, $classMetadata);
        $changed = $this->getChangedRowsCondition($identifiers, $columns);

        $rows = [
            'INSERT' => self::NEW_ROWS,
            'DELETE' => self::OLD_ROWS,
            'UPDATE' => \sprintf(
                '(SELECT n.* FROM %1$s n JOIN %2$s o ON %3$s UNION ALL SELECT o.* FROM %1$s n JOIN %2$s o ON %3$s)',
                self::NEW_ROWS,
                self::OLD_ROWS,
                $changed,
            ),
        ];

        foreach ($rows as $operation => $from) {
            yield [
                $table,
                $operation,
                $this->getInsertDirtyFlagStatement(
                    $entityManager,
                    $platform,
                    $summaryMetadata,
                    \sprintf('%s FROM %s %s%s', $select, $from, $alias, $tail),
                ),
            ];
        }
    }

    /**
     * @return iterable<array{string,string}> The table and the statement.
     */
    private function getRelatedTableStatements(
        EntityManagerInterface $entityManager,
        AbstractPlatform $platform,
        SummaryMetadata $summaryMetadata,
    ): iterable {
        // the properties read through each path, grouped by the table of the
        // related entity

        /** @var array<string,ClassMetadata<object>> */
        $classMetadatas = [];

        /** @var array<string,string> */
        $reversePaths = [];

        /** @var array<string,list<string>> */
        $properties = [];

        foreach ($this->getAssociationDependencies($entityManager, $summaryMetadata) as [$property, $dependency]) {
            $classMetadata = $this->getClassMetadata($entityManager, $dependency->getRelatedClass());
            $table = $this->getTableName($entityManager, $platform, $classMetadata);
            $key = $table . '|' . $dependency->getReversePath();

            $classMetadatas[$key] = $classMetadata;
            $reversePaths[$key] = $dependency->getReversePath();
            $properties[$key][] = $property;
        }

        ksort($classMetadatas);

        foreach ($classMetadatas as $key => $classMetadata) {
            $reversePath = $reversePaths[$key] ?? throw new LogicException('Reverse path not found.');
            $table = $this->getTableName($entityManager, $platform, $classMetadata);
            $columns = $this->getColumns($entityManager, $platform, $classMetadata, $properties[$key] ?? []);

            if ($columns === []) {
                continue;
            }

            $identifiers = $this->getIdentifierColumns($entityManager, $platform, $classMetadata);

            if (\count($identifiers) !== 1) {
                throw new LogicException(\sprintf(
                    'Triggers do not support the composite identifier of "%s".',
                    $classMetadata->getName(),
                ));
            }

            $changedRows = \sprintf(
                '(SELECT n.%1$s FROM %2$s n JOIN %3$s o ON %4$s)',
                $identifiers[0],
                self::NEW_ROWS,
                self::OLD_ROWS,
                $this->getChangedRowsCondition($identifiers, $columns),
            );

            yield [
                $table,
                $this->getInsertDirtyFlagStatement(
                    $entityManager,
                    $platform,
                    $summaryMetadata,
                    $this->getReferringPartitionSelect($entityManager, $summaryMetadata, $reversePath, $changedRows),
                ),
            ];
        }
    }

    /**
     * The dependencies of the summary on the properties of the related
     * entities.
     *
     * @return list<array{string,AssociationDependency}> The property and the
     * dependency.
     */
    private function getAssociationDependencies(
        EntityManagerInterface $entityManager,
        SummaryMetadata $summaryMetadata,
    ): array {
        $dependencies = [];

        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $classMetadata) {
            $sourceMetadata = $this->sourceMetadataFactory
                ->getSourceMetadata($classMetadata->getName());

            foreach ($sourceMetadata->getPropertyToAssociationDependencies() as $property => $propertyDependencies) {
                foreach ($propertyDependencies as $dependency) {
                    if ($dependency->getSummaryClass() === $summaryMetadata->getSummaryClass()) {
                        $dependencies[$dependency->getSignature()] = [$property, $dependency];
                    }
                }
            }
        }

        ksort($dependencies);

        return array_values($dependencies);
    }

    /**
     * The rows whose identifiers are the same before and after the update,
     * and whose columns differ.
     *
     * @param list<string> $identifiers
     * @param list<string> $columns
     */
    private function getChangedRowsCondition(array $identifiers, array $columns): string
    {
        $join = implode(' AND ', array_map(
            static fn(string $column): string => \sprintf('o.%1$s = n.%1$s', $column),
            $identifiers,
        ));

        return \sprintf(
            '%s WHERE ROW(%s) IS DISTINCT FROM ROW(%s)',
            $join,
            implode(', ', array_map(static fn(string $column): string => 'n.' . $column, $columns)),
            implode(', ', array_map(static fn(string $column): string => 'o.' . $column, $columns)),
        );
    }

    private function getInsertDirtyFlagStatement(
        EntityManagerInterface $entityManager,
        AbstractPlatform $platform,
        SummaryMetadata $summaryMetadata,
        string $partitionSelect,
    ): string {
        $classMetadata = $entityManager->getClassMetadata(DirtyFlag::class);
        $quoteStrategy = $entityManager->getConfiguration()->getQuoteStrategy();

        $columns = [];
        $values = [];

        if ($classMetadata->isIdGeneratorSequence()) {
            $columns[] = $quoteStrategy->getColumnName('id', $classMetadata, $platform);
            $values[] = \sprintf(
                'NEXTVAL(%s)',
                $platform->quoteStringLiteral($classMetadata->getSequenceName($platform)),
            );
        } elseif (!$classMetadata->idGenerator->isPostInsertGenerator()) {
            throw new LogicException('The identifier of the dirty flags must be generated by the database.');
        }

        $lowestLevel = PartitionUtil::getLowestLevel(
            $summaryMetadata->getPartition()->getPartitionClass(),
        );

        $fields = [
            'class' => $platform->quoteStringLiteral($summaryMetadata->getSummaryClass()),
            'level' => (string) $lowestLevel,
            'key' => 'x.sclr_0',
            'created' => \sprintf('(NOW() AT TIME ZONE %s)', $platform->quoteStringLiteral($this->timezone)),
        ];

        foreach ($fields as $field => $value) {
            $columns[] = $quoteStrategy->getColumnName($field, $classMetadata, $platform);
            $values[] = $value;
        }

        return \sprintf(
            'INSERT INTO %s (%s) SELECT %s FROM (%s) x WHERE x.sclr_0 IS NOT NULL',
            $quoteStrategy->getTableName($classMetadata, $platform),
            implode(', ', $columns),
            implode(', ', $values),
            $partitionSelect,
        );
    }

    /**
     * Compiles the DQL selecting the lowest partition of the source entity,
     * and splits the SQL around the table of the source entity, so that it
     * can be replaced by a transition table.
     *
     * @return array{string,string,string} The select clause, the alias of
     * the table, and the rest of the SQL after the table, e.g. the condition
     * on the discriminator column.
     */
    private function getPartitionSelect(
        EntityManagerInterface $entityManager,
        SummaryMetadata $summaryMetadata,
    ): array {
        $queryBuilder = $this->createPartitionQueryBuilder($entityManager, $summaryMetadata);
        $sql = $this->getSql($queryBuilder);

        if (preg_match('/^(SELECT DISTINCT .+? AS sclr_0) FROM (\S+) (\w+)(.*)$/s', $sql, $matches) !== 1) {
            throw new LogicException(\sprintf('Unexpected SQL: %s', $sql));
        }

        return [$matches[1], $matches[3], $matches[4]];
    }

    /**
     * Compiles the DQL selecting the lowest partitions of the source
     * entities referring to the given related rows through the path.
     */
    private function getReferringPartitionSelect(
        EntityManagerInterface $entityManager,
        SummaryMetadata $summaryMetadata,
        string $reversePath,
        string $relatedRows,
    ): string {
        $queryBuilder = $this->createPartitionQueryBuilder($entityManager, $summaryMetadata);

        $queryBuilder->andWhere(\sprintf(
            '%s IN (:related)',
            $queryBuilder->resolve($reversePath),
        ));

        $sql = $this->getSql($queryBuilder);

        if (substr_count($sql, '?') !== 1) {
            throw new LogicException(\sprintf('Unexpected SQL: %s', $sql));
        }

        return str_replace('(?)', $relatedRows, $sql);
    }

    private function createPartitionQueryBuilder(
        EntityManagerInterface $entityManager,
        SummaryMetadata $summaryMetadata,
    ): SimpleQueryBuilder {
        // without the filter of the summary, a change can move a row out of
        // the filter, its partition must be refreshed too

        $queryBuilder = SourceQueryBuilderFactory::create(
            entityManager: $entityManager,
            summaryMetadata: $summaryMetadata,
            filtered: false,
        );

        $partitionMetadata = $summaryMetadata->getPartition();
        $partitionClass = $partitionMetadata->getPartitionClass();

        $classifier = $partitionClass::getClassifierExpression(
            input: $partitionMetadata->getSource(),
            level: PartitionUtil::getLowestLevel($partitionClass),
            context: new SourceQueryContext(
                queryBuilder: $queryBuilder,
                summaryMetadata: $summaryMetadata,
                partitionMetadata: $partitionMetadata,
            ),
        );

        $queryBuilder->select(\sprintf('DISTINCT %s', $classifier));

        return $queryBuilder;
    }

    private function getSql(SimpleQueryBuilder $queryBuilder): string
    {
        $sql = $queryBuilder->getQuery()->getSQL();

        if (!\is_string($sql)) {
            throw new LogicException('Expected a single SQL statement.');
        }

        return $sql;
    }

    /**
     * @param class-string $class
     * @return ClassMetadata<object>
     */
    private function getClassMetadata(EntityManagerInterface $entityManager, string $class): ClassMetadata
    {
        $classMetadata = $entityManager->getClassMetadata($class);

        if ($classMetadata->isInheritanceTypeJoined()) {
            throw new LogicException(\sprintf(
                'Triggers do not support the class table inheritance of "%s".',
                $class,
            ));
        }

        return $classMetadata;
    }

    /**
     * @param ClassMetadata<object> $classMetadata
     */
    private function getTableName(
        EntityManagerInterface $entityManager,
        AbstractPlatform $platform,
        ClassMetadata $classMetadata,
    ): string {
        return $entityManager->getConfiguration()->getQuoteStrategy()
            ->getTableName($classMetadata, $platform);
    }

    /**
     * @param ClassMetadata<object> $classMetadata
     * @return list<string>
     */
    private function getIdentifierColumns(
        EntityManagerInterface $entityManager,
        AbstractPlatform $platform,
        ClassMetadata $classMetadata,
    ): array {
        return $entityManager->getConfiguration()->getQuoteStrategy()
            ->getIdentifierColumnNames($classMetadata, $platform);
    }

    /**
     * The columns of the properties, looked up in the class and in its
     * subclasses, which share the table. Properties that are not mapped to a
     * column, e.g. a to-many association, are ignored.
     *
     * @param ClassMetadata<object> $classMetadata
     * @param list<string> $properties
     * @return list<string>
     */
    private function getColumns(
        EntityManagerInterface $entityManager,
        AbstractPlatform $platform,
        ClassMetadata $classMetadata,
        array $properties,
    ): array {
        $quoteStrategy = $entityManager->getConfiguration()->getQuoteStrategy();

        $hierarchy = [
            $classMetadata,
            ...array_map(
                static fn(string $class): ClassMetadata => $entityManager->getClassMetadata($class),
                $classMetadata->subClasses,
            ),
        ];

        $columns = [];

        foreach ($properties as $property) {
            foreach ($hierarchy as $metadata) {
                if ($metadata->hasField($property)) {
                    $columns[] = $quoteStrategy->getColumnName($property, $metadata, $platform);

                    break;
                }

                if ($metadata->hasAssociation($property)) {
                    if ($metadata->isAssociationWithSingleJoinColumn($property)) {
                        $columns[] = $metadata->getSingleAssociationJoinColumnName($property);
                    }

                    break;
                }
            }
        }

        return array_values(array_unique($columns));
    }
}
