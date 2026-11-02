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

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Metadata\Attribute\Groupings;
use Rekalogika\Analytics\Metadata\Util\AttributeUtil;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;

final class AttributeUtilTest extends TestCase
{
    public function testInheritedPropertyAttributesAreNotDuplicated(): void
    {
        $attributes = iterator_to_array(
            AttributeUtil::getPropertyAttributes(OrderSummary::class, '__groupings'),
            false,
        );

        $groupings = array_filter(
            $attributes,
            static fn(object $attribute): bool => $attribute instanceof Groupings,
        );

        $this->assertCount(1, $groupings);
    }
}
