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
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionFactory;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\DimensionByNameCollector;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Tests\App\Entity\Gender;
use Symfony\Component\Translation\TranslatableMessage;

final class DimensionCollectorTest extends TestCase
{
    public function testNullLast(): void
    {
        $dimensionFactory = new DimensionFactory(new HardcodedOrderByResolver(Order::Ascending));

        $collector = new DimensionByNameCollector(
            'gender',
            Order::Ascending,
            $dimensionFactory,
        );

        $collector->addDimension(new DefaultDimension(
            label: new TranslatableMessage('Female'),
            name: 'gender',
            rawMember: Gender::Female,
            member: Gender::Female,
            displayMember: Gender::Female,
        ));

        $collector->addDimension(new DefaultDimension(
            label: new TranslatableMessage('Male'),
            name: 'gender',
            rawMember: Gender::Male,
            member: Gender::Male,
            displayMember: Gender::Male,
        ));

        $collector->addDimension(new DefaultDimension(
            label: new TranslatableMessage('Other'),
            name: 'gender',
            rawMember: Gender::Other,
            member: Gender::Other,
            displayMember: Gender::Other,
        ));

        $collector->addDimension(new DefaultDimension(
            label: new TranslatableMessage('Unknown'),
            name: 'gender',
            rawMember: null,
            member: null,
            displayMember: 'Unknown',
        ));

        $result = array_map(
            fn($dimension): mixed => $dimension->getRawMember(),
            iterator_to_array($collector->getResult()),
        );

        // Ensure the null value is last in the result
        $this->assertCount(4, $result);
        $this->assertNull($result[3]);
    }
}
