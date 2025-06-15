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
use Rekalogika\Analytics\Uuid\ValueResolver\UuidToTruncatedInteger;

final class UuidToTruncatedIntegerTest extends TestCase
{
    public function testTransform(): void
    {
        $resolver = new UuidToTruncatedInteger('foo');
        $uuid = '01943d05-942a-7e95-a9a1-bed59b37c877';

        $transformed = $resolver->transformSourceValueToSummaryValue($uuid);
        /** @psalm-suppress MixedAssignment */
        $reversed = $resolver->transformSummaryValueToSourceValue($transformed);

        $this->assertEquals('01943d05-942a-0000-0000-000000000000', $reversed);
    }

    public function testTransform2(): void
    {
        $resolver = new UuidToTruncatedInteger('foo');
        $uuid = '01963250-b507-74c8-8f78-f6bac13088ec';

        $transformed = $resolver->transformSourceValueToSummaryValue($uuid);
        /** @psalm-suppress MixedAssignment */
        $reversed = $resolver->transformSummaryValueToSourceValue($transformed);

        $this->assertEquals('01963250-b507-0000-0000-000000000000', $reversed);
    }
}
