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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Metadata\Util\InvolvedPropertyUtil;

final class InvolvedPropertyUtilTest extends TestCase
{
    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideChangeSetKeys')]
    public function testGetChangeSetKeys(string $path, array $expected): void
    {
        $this->assertSame($expected, InvolvedPropertyUtil::getChangeSetKeys($path));
    }

    /**
     * @return iterable<string,array{string,list<string>}>
     */
    public static function provideChangeSetKeys(): iterable
    {
        yield 'property' => ['name', ['name']];
        yield 'property with whitespace' => [' name ', ['name']];
        yield 'association or embeddable' => ['customer.country', ['customer', 'customer.country']];
        yield 'nested embeddable' => ['a.b.c', ['a', 'a.b.c']];
        yield 'association alias' => ['customer.*', ['customer']];
        yield 'association cast' => ['customer(App\Entity\IndividualCustomer).gender', ['customer']];
        yield 'cast after the first segment' => ['item.category(App\Entity\Category).name', ['item']];
        yield 'root cast' => ['(App\Entity\IndividualCustomer).gender', ['gender']];
        yield 'root cast then association' => ['(App\Entity\Loan).loanee.name', ['loanee', 'loanee.name']];
        yield 'root cast then association cast' => ['(App\Entity\Loan).loanee(App\Entity\Person).name', ['loanee']];
        yield 'root alias' => ['*', []];
        yield 'root cast alias' => ['(App\Entity\IndividualCustomer).*', []];
        yield 'empty' => ['', []];
    }
}
