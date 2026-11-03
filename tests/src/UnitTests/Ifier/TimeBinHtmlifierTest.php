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
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekWeek;
use Rekalogika\Analytics\Time\Formatter\TimeBinHtmlifier;
use Rekalogika\Analytics\Time\TimeBin;

final class TimeBinHtmlifierTest extends TestCase
{
    public function testOutputIsEscaped(): void
    {
        $stringifier = $this->createStub(Stringifier::class);
        $stringifier->method('toString')->willReturnCallback(
            static fn(mixed $input): string => $input instanceof TimeBin
                ? '<script>&'
                : 'a "title" <b>',
        );

        $htmlifier = new TimeBinHtmlifier($stringifier);
        $html = $htmlifier->toHtml(IsoWeekWeek::createFromDatabaseValue(202401));

        $this->assertSame(
            '<span title="a &quot;title&quot; &lt;b&gt;">&lt;script&gt;&amp;</span>',
            $html,
        );
    }
}
