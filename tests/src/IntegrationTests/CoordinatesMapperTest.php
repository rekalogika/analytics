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

use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CoordinatesMapperTest extends KernelTestCase
{
    public function testCoordinatesMapper(): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        // query

        $query = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerCountry')
            ->addDimension('itemCategory')
            ->addDimension('customerType');

        $result = $query->getResult();

        // test all rows

        $cells = $result->getCube()
            ->drillDown(['customerCountry', 'itemCategory', 'customerType']);

        foreach ($cells as $currentCell) {
            $this->testOne($currentCell);
        }
    }

    private function testOne(Cell $cell): void
    {
        $coordinatesMapper = self::getContainer()->get(CoordinatesMapper::class);
        $this->assertInstanceOf(CoordinatesMapper::class, $coordinatesMapper);

        $coordinates = $cell->getCoordinates();
        $class = $coordinates->getSummaryClass();

        $coordinatesDto = $coordinatesMapper->toDto($coordinates);

        // deserialize the coordinates

        $newRow = $coordinatesMapper->fromDto($class, $coordinatesDto);

        // original measures

        /** @psalm-suppress MixedAssignment */
        $count = $cell->getMeasures()->get('count')?->getRawValue();
        /** @psalm-suppress MixedAssignment */
        $price = $cell->getMeasures()->get('price')?->getRawValue();

        /** @psalm-suppress MixedAssignment */
        $deserializedCount = $newRow->getMeasures()->get('count')?->getRawValue();
        /** @psalm-suppress MixedAssignment */
        $deserializedPrice = $newRow->getMeasures()->get('price')?->getRawValue();

        $this->assertEquals($count, $deserializedCount);
        $this->assertEquals($price, $deserializedPrice);
    }
}
