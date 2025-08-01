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

final class Version20250703 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Creates the REKALOGIKA_TIME_BIN_MBW_* functions. Used for converting the input time to month-based-week bins.';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        // input is date, which is always without time zone. we don't do any
        // time zone conversion

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION REKALOGIKA_TIME_BIN_MBW_WEEK(
                input date,
                inputtz text,
                outputtz text
            ) RETURNS INTEGER
            AS $$
                WITH
                -- Step 1: Calculate the ISO week’s Thursday (week owning date)
                week_thursday AS (
                    SELECT (input + (4 - EXTRACT(ISODOW FROM input)::INT) * INTERVAL '1 day')::DATE AS thursday
                ),

                -- Step 2: Determine the year and month from that Thursday
                year_month AS (
                    SELECT
                        thursday,
                        EXTRACT(YEAR FROM thursday)::INT AS y,
                        EXTRACT(MONTH FROM thursday)::INT AS m
                    FROM week_thursday
                ),

                -- Step 3: First Thursday of that month
                first_thursday AS (
                    SELECT
                        y,
                        m,
                        thursday,
                        (MAKE_DATE(y, m, 1) + ((11 - EXTRACT(DOW FROM MAKE_DATE(y, m, 1))::INT) % 7) * INTERVAL '1 day')::DATE AS first_thu
                    FROM year_month
                ),

                -- Step 4: Compute Mondays
                mondays AS (
                    SELECT
                        y,
                        m,
                        thursday,
                        (first_thu - ((EXTRACT(ISODOW FROM first_thu)::INT + 6) % 7) * INTERVAL '1 day')::DATE AS first_week_monday,
                        (input - ((EXTRACT(ISODOW FROM input)::INT + 6) % 7) * INTERVAL '1 day')::DATE AS current_week_monday
                    FROM first_thursday
                )

                -- Step 5: Calculate final YYYYMMW integer
                SELECT
                    y * 1000 + m * 10 + GREATEST(((current_week_monday - first_week_monday) / 7)::INT + 1, 1)
                FROM mondays;
            $$ LANGUAGE SQL IMMUTABLE;
        SQL);

        // input is timestamp with time zone, we ignore the input time zone
        // and convert the input to the output time zone

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION REKALOGIKA_TIME_BIN_MBW_WEEK(
                input timestamptz,
                inputtz text,
                outputtz text
            ) RETURNS integer
            AS $$
                SELECT REKALOGIKA_TIME_BIN_MBW_WEEK(
                    (input AT TIME ZONE outputtz)::date,
                    inputtz,
                    outputtz
                )
            $$ LANGUAGE sql IMMUTABLE STRICT;
        SQL);

        // input is timestamp without time zone, we convert the input to the
        // time zone provided by the caller, and then convert it to the
        // output time zone

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION REKALOGIKA_TIME_BIN_MBW_WEEK(
                input timestamp,
                inputtz text,
                outputtz text
            ) RETURNS integer
            AS $$
                SELECT REKALOGIKA_TIME_BIN_MBW_WEEK(
                    (input AT TIME ZONE inputtz AT TIME ZONE outputtz)::date,
                    inputtz,
                    outputtz
                )
            $$ LANGUAGE sql IMMUTABLE STRICT;
        SQL);
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP FUNCTION IF EXISTS REKALOGIKA_TIME_BIN_MBW_WEEK(date, text, text);
        SQL);

        $this->addSql(<<<'SQL'
            DROP FUNCTION IF EXISTS REKALOGIKA_TIME_BIN_MBW_WEEK(timestamptz, text, text);
        SQL);

        $this->addSql(<<<'SQL'
            DROP FUNCTION IF EXISTS REKALOGIKA_TIME_BIN_MBW_WEEK(timestamp, text, text);
        SQL);
    }
}
