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

namespace Rekalogika\Analytics\Tests\IntegrationTests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Frontend\Html\PredicateRenderer;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A NOT expression with more than one child cannot be expressed in DQL, the
 * extra children must not be silently dropped.
 */
final class NotExpressionTest extends KernelTestCase
{
    public function testQueryRejectsMultiChildNot(): void
    {
        $query = $this->createQuery();

        $this->expectException(InvalidArgumentException::class);
        $query->getResult();
    }

    public function testRendererRejectsMultiChildNot(): void
    {
        $query = $this->createQuery();

        $this->expectException(LogicException::class);
        static::getContainer()->get(PredicateRenderer::class)->renderPredicate($query);
    }

    public function testSingleChildNot(): void
    {
        $query = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerCountry')
            ->dice(Criteria::expr()->not(
                Criteria::expr()->eq('time.civil.year', 1900),
            ));

        $this->assertGreaterThan(0, \count($query->getResult()->drillDown('customerCountry')));

        $rendered = static::getContainer()
            ->get(PredicateRenderer::class)
            ->renderPredicate($query);

        $this->assertCount(1, $rendered);
        $this->assertStringStartsWith('NOT ', $rendered[0]);
    }

    private function createQuery(): Query
    {
        // doctrine/collections 3 rejects this in the constructor, but older
        // versions and hand-made instances do not.
        $reflection = new \ReflectionClass(CompositeExpression::class);
        $not = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('type')->setValue($not, CompositeExpression::TYPE_NOT);
        $reflection->getProperty('expressions')->setValue($not, [
            Criteria::expr()->eq('time.civil.year', 1900),
            Criteria::expr()->eq('time.civil.year', 1901),
        ]);

        return static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerCountry')
            ->dice($not);
    }
}
