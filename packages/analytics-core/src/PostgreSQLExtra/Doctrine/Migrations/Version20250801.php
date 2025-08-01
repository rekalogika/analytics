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

namespace Rekalogika\Analytics\PostgreSQLExtra\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Creates the REKALOGIKA_FIRST and REKALOGIKA_LAST aggregate functions.';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        // Create a function that always returns the first non-NULL value:
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.rekalogika_first_agg (anyelement, anyelement)
                RETURNS anyelement
                LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE AS
            'SELECT $1';
        SQL);

        // Then wrap an aggregate around it:
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE AGGREGATE public.rekalogika_first (anyelement) (
                SFUNC    = public.rekalogika_first_agg
                , STYPE    = anyelement
                , PARALLEL = safe
            );
        SQL);

        // Create a function that always returns the last non-NULL value:
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.rekalogika_last_agg (anyelement, anyelement)
                RETURNS anyelement
                LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE AS
            'SELECT $2';
        SQL);

        // Then wrap an aggregate around it:
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE AGGREGATE public.rekalogika_last (anyelement) (
                SFUNC    = public.rekalogika_last_agg
                , STYPE    = anyelement
                , PARALLEL = safe
            );
        SQL);
    }

    #[\Override]
    public function down(Schema $schema): void {}
}
