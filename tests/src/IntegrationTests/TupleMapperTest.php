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

use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Serialization\TupleMapper;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TupleMapperTest extends KernelTestCase
{
    public function testTupleMapper(): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        // query

        $query = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->select('count', 'price')
            ->groupBy('customerCountry')
            ->addGroupBy('itemCategory')
            ->addGroupBy('customerType');

        $result = $query->getResult();

        // test all rows

        foreach ($result->getTable() as $currentRow) {
            $this->testOne($currentRow);
        }
    }

    private function testOne(Row $row): void
    {
        $tupleMapper = self::getContainer()->get(TupleMapper::class);
        $this->assertInstanceOf(TupleMapper::class, $tupleMapper);

        $tuple = $row->getTuple();
        $class = $tuple->getSummaryClass();

        $tupleDto = $tupleMapper->toDto($tuple);

        // deserialize the tuple

        $newRow = $tupleMapper->fromDto($class, $tupleDto);

        // original measures

        /** @psalm-suppress MixedAssignment */
        $count = $row->getMeasures()->getByKey('count')?->getRawValue();
        /** @psalm-suppress MixedAssignment */
        $price = $row->getMeasures()->getByKey('price')?->getRawValue();

        /** @psalm-suppress MixedAssignment */
        $deserializedCount = $newRow->getMeasures()->getByKey('count')?->getRawValue();
        /** @psalm-suppress MixedAssignment */
        $deserializedPrice = $newRow->getMeasures()->getByKey('price')?->getRawValue();

        $this->assertEquals($count, $deserializedCount);
        $this->assertEquals($price, $deserializedPrice);
    }
}
