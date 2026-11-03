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
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AbstractQueryTest extends KernelTestCase
{
    public function testCloneDoesNotReuseCachedQueryComponents(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: Order::class,
            alias: 'root',
        );

        $simpleQueryBuilder->select('root.id');

        $original = new class ($simpleQueryBuilder) extends AbstractQuery {
            public function __construct(SimpleQueryBuilder $simpleQueryBuilder)
            {
                parent::__construct($simpleQueryBuilder);
            }

            public function addCondition(): void
            {
                $this->getSimpleQueryBuilder()
                    ->andWhere($this->resolve('customer.name') . ' IS NOT NULL');
            }
        };

        $originalSql = $original->getQueryComponents()->getSqlStatement();

        $clone = clone $original;
        $clone->addCondition();

        $cloneSql = $clone->getQueryComponents()->getSqlStatement();

        $this->assertNotSame($original->getQueryComponents(), $clone->getQueryComponents());
        $this->assertStringNotContainsString('IS NOT NULL', $originalSql);
        $this->assertStringContainsString('IS NOT NULL', $cloneSql);
        $this->assertSame($originalSql, $original->getQueryComponents()->getSqlStatement());
    }
}
