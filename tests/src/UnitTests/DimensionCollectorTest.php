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
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\DimensionCollector\DimensionByKeyCollector;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Tests\App\Entity\Gender;
use Symfony\Component\Translation\TranslatableMessage;

final class DimensionCollectorTest extends TestCase
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions = [];

    private function addDimension(string $id, DefaultDimension $dimension): void
    {
        $this->dimensions[$id] = $dimension;
    }

    private function getDimension(string $id): DefaultDimension
    {
        if (!isset($this->dimensions[$id])) {
            throw new \InvalidArgumentException(\sprintf('Dimension "%s" not found', $id));
        }

        return $this->dimensions[$id];
    }

    #[\Override]
    public function setUp(): void
    {
        $this->addDimension('male', new DefaultDimension(
            label: new TranslatableMessage('Male'),
            key: 'gender',
            rawMember: Gender::Male,
            member: Gender::Male,
            displayMember: Gender::Male,
        ));

        $this->addDimension('female', new DefaultDimension(
            label: new TranslatableMessage('Female'),
            key: 'gender',
            rawMember: Gender::Female,
            member: Gender::Female,
            displayMember: Gender::Female,
        ));

        $this->addDimension('other', new DefaultDimension(
            label: new TranslatableMessage('Other'),
            key: 'gender',
            rawMember: Gender::Other,
            member: Gender::Other,
            displayMember: Gender::Other,
        ));

        $this->addDimension('null', new DefaultDimension(
            label: new TranslatableMessage('Unknown'),
            key: 'gender',
            rawMember: null,
            member: null,
            displayMember: 'Unknown',
        ));
    }

    public function testDimensionCollector(): void
    {
        $collector = new DimensionByKeyCollector('gender');

        $collector->addDimension([], $this->getDimension('female'));
        $collector->addDimension([], $this->getDimension('male'));
        $collector->addDimension([], $this->getDimension('other'));
        $collector->addDimension([], $this->getDimension('null'));

        $this->assertEquals([
            Gender::Female,
            Gender::Male,
            Gender::Other,
            null,
        ], array_map(fn($dimension): mixed => $dimension->getRawMember(), iterator_to_array($collector->getResult())));
    }
}
