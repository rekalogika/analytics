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

namespace Rekalogika\Analytics\Core\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class BustMigration extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Creates the REKALOGIKA_BUST function. This is used to wrap expressions so that two identical expressions will not cause issues in a roll-up queries.';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION REKALOGIKA_BUST(arg anyelement, dummy integer)
            RETURNS anyelement
            LANGUAGE sql
            AS $$
                SELECT arg;
            $$;
        SQL);
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP FUNCTION IF EXISTS REKALOGIKA_BUST(anyelement, integer);
        SQL);
    }
}
