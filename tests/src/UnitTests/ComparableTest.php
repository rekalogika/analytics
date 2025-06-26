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
use Rekalogika\Analytics\Engine\Util\ComparableUtil;
use Rekalogika\Analytics\Time\Bin\Date;

final class ComparableTest extends TestCase
{
    public function testSort(): void
    {
        $items = [
            Date::createFromDatabaseValue(20250102),
            Date::createFromDatabaseValue(20250101),
            Date::createFromDatabaseValue(20250105),
            Date::createFromDatabaseValue(20250104),
            Date::createFromDatabaseValue(20250103),
        ];

        $items = ComparableUtil::sort($items, Order::Ascending);

        $this->assertEquals(
            [
                Date::createFromDatabaseValue(20250101),
                Date::createFromDatabaseValue(20250102),
                Date::createFromDatabaseValue(20250103),
                Date::createFromDatabaseValue(20250104),
                Date::createFromDatabaseValue(20250105),
            ],
            $items,
        );
    }

    public function testSortDescending(): void
    {
        $items = [
            Date::createFromDatabaseValue(20250102),
            Date::createFromDatabaseValue(20250101),
            Date::createFromDatabaseValue(20250105),
            Date::createFromDatabaseValue(20250104),
            Date::createFromDatabaseValue(20250103),
        ];

        $items = ComparableUtil::sort($items, Order::Descending);

        $this->assertEquals(
            [
                Date::createFromDatabaseValue(20250105),
                Date::createFromDatabaseValue(20250104),
                Date::createFromDatabaseValue(20250103),
                Date::createFromDatabaseValue(20250102),
                Date::createFromDatabaseValue(20250101),
            ],
            $items,
        );
    }
}
