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

namespace Rekalogika\Analytics\Tests\SimpleQueryBuilder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;
use Symfony\Component\Uid\Uuid;

final class QueryComponentsTest extends TestCase
{
    /**
     * @return iterable<string,array{mixed,string}>
     */
    public static function provideEscapeFunction(): iterable
    {
        yield 'string' => ["it's", "'it\\'s'"];
        yield 'null' => [null, 'NULL'];
        yield 'bool' => [true, '1'];
        yield 'int' => [42, '42'];
        yield 'array' => [['a', 1], "'a', 1"];

        yield 'stringable' => [
            Uuid::fromString('0192f0e4-5f5e-7c3a-9a6b-2f0d1e3c4b5a'),
            "'0192f0e4-5f5e-7c3a-9a6b-2f0d1e3c4b5a'",
        ];
    }

    #[DataProvider('provideEscapeFunction')]
    public function testEscapeFunction(mixed $parameter, string $expected): void
    {
        $this->assertSame($expected, QueryComponents::escapeFunction($parameter));
    }
}
