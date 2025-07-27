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
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Contracts\Serialization\TupleSerializer;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TupleSerializerTest extends KernelTestCase
{
    public function testTupleSerializer(): void
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
        $tupleSerializer = self::getContainer()->get(TupleSerializer::class);
        $this->assertInstanceOf(TupleSerializer::class, $tupleSerializer);

        $serializedTuple = $tupleSerializer->serialize($row);

        // deserialize the tuple

        $deserializedRow = $tupleSerializer->deserialize($serializedTuple);

        // original measures

        /** @psalm-suppress MixedAssignment */
        $count = $row->getMeasures()->getByName('count')?->getRawValue();
        /** @psalm-suppress MixedAssignment */
        $price = $row->getMeasures()->getByName('price')?->getRawValue();

        /** @psalm-suppress MixedAssignment */
        $deserializedCount = $deserializedRow->getMeasures()->getByName('count')?->getRawValue();
        /** @psalm-suppress MixedAssignment */
        $deserializedPrice = $deserializedRow->getMeasures()->getByName('price')?->getRawValue();

        $this->assertEquals($count, $deserializedCount);
        $this->assertEquals($price, $deserializedPrice);
    }
}
