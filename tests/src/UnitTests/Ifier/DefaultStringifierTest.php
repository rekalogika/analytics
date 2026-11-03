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

namespace Rekalogika\Analytics\Tests\UnitTests\Ifier;

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Frontend\Formatter\Implementation\DefaultStringifier;

final class DefaultStringifierTest extends TestCase
{
    public function testUnknownObjectDoesNotLeakClassName(): void
    {
        $result = (new DefaultStringifier())->toString(new \ArrayObject());

        $this->assertSame('object', $result);
    }

    public function testStringableObject(): void
    {
        $object = new class implements \Stringable {
            #[\Override]
            public function __toString(): string
            {
                return 'foo';
            }
        };

        $this->assertSame('foo', (new DefaultStringifier())->toString($object));
    }
}
