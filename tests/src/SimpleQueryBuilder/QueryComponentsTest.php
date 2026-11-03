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
        yield 'string' => ['foo', "'foo'"];
        yield 'string with quote' => ["it's", "'it''s'"];
        yield 'string with backslash' => ['a\\b', "'a\\b'"];
        yield 'string with backslash and quote' => ["\\' OR 1=1--", "'\\'' OR 1=1--'"];
        yield 'non-utf8 string' => ["\xff\x00A", "'\\xff0041'::bytea"];
        yield 'null' => [null, 'NULL'];
        yield 'true' => [true, 'TRUE'];
        yield 'false' => [false, 'FALSE'];
        yield 'int' => [42, '42'];
        yield 'float' => [1.5, '1.5'];
        yield 'array' => [['a', 1], "'a', 1"];
        yield 'empty array' => [[], 'NULL'];

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
