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

namespace Rekalogika\Analytics\Tests\SimpleQueryBuilder;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class QueryComponentsInterpolationTest extends KernelTestCase
{
    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testPlaceholdersInsideStringLiteralsAreNotReplaced(): void
    {
        $query = $this->getEntityManager()
            ->createQuery(\sprintf(
                "SELECT o.id FROM %s o
                WHERE o.id = :id
                AND CONCAT('What?', 'HH24:MI', 'it''s ?') <> :label
                AND o.id <> :other",
                Order::class,
            ))
            ->setParameter('id', 1)
            ->setParameter('label', "O'Reilly")
            ->setParameter('other', 2);

        $queryComponents = new QueryComponents($query);
        $sql = $queryComponents->getSqlStatement();

        // sanity check: the literals are in the SQL as is
        $this->assertStringContainsString("'What?'", $sql);
        $this->assertStringContainsString("'HH24:MI'", $sql);
        $this->assertStringContainsString("'it''s ?'", $sql);

        $interpolated = $queryComponents->getInterpolatedSqlStatement();

        $this->assertStringContainsString("'What?'", $interpolated);
        $this->assertStringContainsString("'HH24:MI'", $interpolated);
        $this->assertStringContainsString("'it''s ?'", $interpolated);
        $this->assertStringContainsString("= 1", $interpolated);
        $this->assertStringContainsString("<> 'O''Reilly'", $interpolated);
        $this->assertStringContainsString("<> 2", $interpolated);

        // no placeholder is left outside the literals
        $withoutLiterals = str_replace(
            ["'What?'", "'it''s ?'"],
            '',
            $interpolated,
        );

        $this->assertStringNotContainsString('?', $withoutLiterals);
    }

    public function testRepeatedNamedParameter(): void
    {
        $query = $this->getEntityManager()
            ->createQuery(\sprintf(
                'SELECT o.id FROM %s o WHERE o.id = :id OR o.id = :other OR o.id = :id',
                Order::class,
            ))
            ->setParameter('id', 1)
            ->setParameter('other', 2);

        $interpolated = (new QueryComponents($query))
            ->getInterpolatedSqlStatement();

        $this->assertMatchesRegularExpression(
            '/= 1 OR .* = 2 OR .* = 1/',
            $interpolated,
        );
    }
}
