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
use Rekalogika\Analytics\UX\PanelBundle\Filter\Choice\Choice;

final class ChoiceTest extends TestCase
{
    public function testStringHtmlLabel(): void
    {
        $choice = new Choice(
            id: 'a',
            value: 'a',
            label: 'A',
            htmlLabel: '<b>A</b>',
            selected: false,
        );

        $this->assertSame('<b>A</b>', $choice->getHtmlLabel());
    }

    public function testHtmlLabelIsComputedLazilyAndOnlyOnce(): void
    {
        $htmlifier = new class {
            private int $calls = 0;

            public function __invoke(): string
            {
                ++$this->calls;

                return '<b>A</b>';
            }

            public function getCalls(): int
            {
                return $this->calls;
            }
        };

        $choice = new Choice(
            id: 'a',
            value: 'a',
            label: 'A',
            htmlLabel: $htmlifier(...),
            selected: true,
        );

        $this->assertSame('A', $choice->getLabel());
        $this->assertTrue($choice->isSelected());
        $this->assertSame(0, $htmlifier->getCalls());

        $this->assertSame('<b>A</b>', $choice->getHtmlLabel());
        $this->assertSame('<b>A</b>', $choice->getHtmlLabel());
        $this->assertSame(1, $htmlifier->getCalls());
    }
}
