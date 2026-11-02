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
use Rekalogika\Analytics\Contracts\Dto\CoordinatesDto;
use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Serialization\Implementation\NullCell;
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

        $cubeCell = $query->getResult();

        // test all rows

        $cells = $cubeCell
            ->drillDown(['customerCountry', 'itemCategory', 'customerType']);

        foreach ($cells as $currentCell) {
            $this->testOne($currentCell);
        }
    }

    public function testRoundTripThroughStrings(): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        $cells = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('time.civil.month.month', 'customerType')
            ->getResult()
            ->drillDown(['time.civil.month.month', 'customerType']);

        $this->assertGreaterThan(0, \count($cells));

        foreach ($cells as $cell) {
            // members arrive as strings when they come from a URL
            $this->testOne($cell, stringify: true);
        }
    }

    /**
     * The dice leaves a gap of two months, which the gap filler fills with
     * interpolated members. Their cells must round trip like the others.
     */
    public function testRoundTripOfInterpolatedMembers(): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        $cells = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('time.civil.month.month', 'customerType')
            ->dice(Criteria::expr()->in('time.civil.month.month', [202301, 202304]))
            ->getResult()
            ->drillDown(['time.civil.month.month', 'customerType']);

        $months = [];

        foreach ($cells as $cell) {
            /** @psalm-suppress MixedAssignment */
            $month = $cell->getCoordinates()->get('time.civil.month.month')?->getRawMember();
            $this->assertIsInt($month);
            $months[$month] = true;

            $this->testOne($cell);
            $this->testOne($cell, stringify: true);
        }

        $this->assertSame([202301, 202302, 202303, 202304], array_keys($months));
    }

    public function testMemberMissingFromResultYieldsNullCell(): void
    {
        $coordinatesMapper = self::getContainer()->get(CoordinatesMapper::class);
        $this->assertInstanceOf(CoordinatesMapper::class, $coordinatesMapper);

        $dto = new CoordinatesDto(
            members: ['time.civil.month.month' => '190001'],
            predicate: null,
        );

        $cell = $coordinatesMapper->fromDto(OrderSummary::class, $dto);

        $this->assertInstanceOf(NullCell::class, $cell);
        $this->assertNull($cell->getMeasures()->get('count')?->getRawValue());
    }

    private function testOne(Cell $cell, bool $stringify = false): void
    {
        $coordinatesMapper = self::getContainer()->get(CoordinatesMapper::class);
        $this->assertInstanceOf(CoordinatesMapper::class, $coordinatesMapper);

        $coordinates = $cell->getCoordinates();
        $class = $coordinates->getSummaryClass();

        $coordinatesDto = $coordinatesMapper->toDto($coordinates);

        if ($stringify) {
            $coordinatesDto = new CoordinatesDto(
                members: array_map(
                    static fn(int|string|null $member): ?string => $member === null ? null : (string) $member,
                    $coordinatesDto->getMembers(),
                ),
                predicate: $coordinatesDto->getPredicate(),
            );
        }

        // deserialize the coordinates

        $newRow = $coordinatesMapper->fromDto($class, $coordinatesDto);
        $this->assertNotInstanceOf(NullCell::class, $newRow);

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
