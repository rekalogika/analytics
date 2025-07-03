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
                SELECT
                    (EXTRACT(YEAR FROM month_owner_date)::int) * 1000 +
                    (EXTRACT(MONTH FROM month_owner_date)::int) * 10 +
                    (FLOOR(
                        EXTRACT(EPOCH FROM (
                            week_monday - DATE_TRUNC('week', first_thursday_of_month + INTERVAL '3 days')
                        )) / 604800
                    )::int + 1)
                FROM (
                    SELECT
                        -- ISO-style week Monday
                        DATE_TRUNC('week', input + INTERVAL '3 days')::date AS week_monday,

                        -- The Thursday of that week, to determine ownership
                        (DATE_TRUNC('week', input + INTERVAL '3 days') + INTERVAL '3 days')::date AS week_thursday,

                        -- First Thursday of that month (the anchor for week 1)
                        (
                            SELECT d
                            FROM generate_series(
                                DATE_TRUNC('month', (DATE_TRUNC('week', input + INTERVAL '3 days') + INTERVAL '3 days'))::date,
                                DATE_TRUNC('month', (DATE_TRUNC('week', input + INTERVAL '3 days') + INTERVAL '3 days'))::date + INTERVAL '6 days',
                                INTERVAL '1 day'
                            ) AS d
                            WHERE EXTRACT(DOW FROM d) = 4
                            LIMIT 1
                        ) AS first_thursday_of_month,

                        -- Month that owns this ISO-style week
                        DATE_TRUNC('month', (DATE_TRUNC('week', input + INTERVAL '3 days') + INTERVAL '3 days'))::date AS month_owner_date
                ) AS data
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
