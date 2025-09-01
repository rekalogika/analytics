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

namespace Rekalogika\Analytics\Tests\UnitTests;

use Doctrine\Common\Collections\Order;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\DimensionCollection;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultDimension;
use Rekalogika\Analytics\Tests\App\Entity\Gender;
use Symfony\Component\Translation\TranslatableMessage;

final class DimensionFactoryTest extends TestCase
{
    public function testNullLast(): void
    {
        $dimensionFactory = new DimensionFactory(
            nodesLimit: 1000,
        );

        $dimensionCollection = new DimensionCollection(
            dimensionFactory: $dimensionFactory,
            orderByResolver: new HardcodedOrderByResolver(Order::Ascending),
        );

        $dimension = $dimensionFactory->createDimension(
            label: new TranslatableMessage('Female'),
            name: 'gender',
            rawMember: Gender::Female,
            member: Gender::Female,
            displayMember: Gender::Female,
            interpolation: false,
        );

        $dimensionCollection->collectDimension($dimension);

        $dimension = $dimensionFactory->createDimension(
            label: new TranslatableMessage('Male'),
            name: 'gender',
            rawMember: Gender::Male,
            member: Gender::Male,
            displayMember: Gender::Male,
            interpolation: false,
        );

        $dimensionCollection->collectDimension($dimension);

        $dimension = $dimensionFactory->createDimension(
            label: new TranslatableMessage('Other'),
            name: 'gender',
            rawMember: Gender::Other,
            member: Gender::Other,
            displayMember: Gender::Other,
            interpolation: false,
        );

        $dimensionCollection->collectDimension($dimension);

        $dimension = $dimensionFactory->createDimension(
            label: new TranslatableMessage('Unknown'),
            name: 'gender',
            rawMember: null,
            member: null,
            displayMember: 'Unknown',
            interpolation: false,
        );

        $dimensionCollection->collectDimension($dimension);

        $dimensionsByName = $dimensionCollection->getDimensionsByName('gender');

        $result = array_map(
            fn(DefaultDimension $dimension): mixed => $dimension->getRawMember(),
            iterator_to_array($dimensionsByName->getGapFilled()),
        );

        // Ensure the null value is last in the result
        $this->assertCount(4, $result);
        $this->assertNull($result[3]);
    }
}
