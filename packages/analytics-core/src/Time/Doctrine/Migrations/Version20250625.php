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

namespace Rekalogika\Analytics\Time\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250625 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Creates the REKALOGIKA_TIME_BIN function. Used for converting the input time to its bin value.';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        // input is timestamp without time zone, we convert the input to the
        // time zone provided by the caller, and then convert it to the
        // output time zone

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION REKALOGIKA_TIME_BIN(
                input timestamp,
                inputtz text,
                outputtz text,
                pattern text
            ) RETURNS integer
            AS $$
                SELECT TO_CHAR(input AT TIME ZONE inputtz AT TIME ZONE outputtz, pattern)::integer
            $$ LANGUAGE sql IMMUTABLE STRICT;
        SQL);

        // input is timestamp with time zone, we ignore the input time zone
        // and convert the input to the output time zone

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION REKALOGIKA_TIME_BIN(
                input timestamptz,
                inputtz text,
                outputtz text,
                pattern text
            ) RETURNS integer
            AS $$
                SELECT TO_CHAR(input AT TIME ZONE outputtz, pattern)::integer
            $$ LANGUAGE sql IMMUTABLE STRICT;
        SQL);

        // input is date, which is always without time zone. we don't do any
        // time zone conversion

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION REKALOGIKA_TIME_BIN(
                input date,
                inputtz text,
                outputtz text,
                pattern text
            ) RETURNS integer
            AS $$
                SELECT TO_CHAR(input, pattern)::integer
            $$ LANGUAGE sql IMMUTABLE STRICT;
        SQL);
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP FUNCTION IF EXISTS REKALOGIKA_TIME_BIN(timestamp, text, text, text);
        SQL);

        $this->addSql(<<<'SQL'
            DROP FUNCTION IF EXISTS REKALOGIKA_TIME_BIN(timestamptz, text, text, text);
        SQL);

        $this->addSql(<<<'SQL'
            DROP FUNCTION IF EXISTS REKALOGIKA_TIME_BIN(date, text, text, text);
        SQL);
    }
}
