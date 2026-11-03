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

namespace Rekalogika\Analytics\Tests\UnitTests\SummaryMetadataFactory;

use Rekalogika\Analytics\Contracts\Translation\LiteralString;
use Rekalogika\Analytics\Core\AggregateFunction\Sum;
use Rekalogika\Analytics\Core\Partition\DefaultIntegerPartition;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;
use Rekalogika\Analytics\Tests\App\Entity\Order;

/**
 * Summary fixture for DefaultSummaryMetadataFactoryTest. Not mapped by
 * Doctrine, the test supplies the class metadata. The properties are public
 * only to keep static analysis from flagging them as unused.
 */
#[Analytics\Summary(sourceClass: Order::class)]
final class UnitSummary
{
    #[Analytics\Groupings]
    public ?string $groupings = null;

    #[Analytics\Partition(new IntegerValue('id'))]
    public ?DefaultIntegerPartition $partition = null;

    #[Analytics\Dimension(source: new PropertyValue('name'))]
    public ?string $name = null;

    #[Analytics\Measure(function: new Sum('stringUnit'), unit: 'kg')]
    public ?int $stringUnit = null;

    #[Analytics\Measure(function: new Sum('literalUnit'), unit: new LiteralString('kg'))]
    public ?int $literalUnit = null;

    #[Analytics\Measure(function: new Sum('otherUnit'), unit: 'lb')]
    public ?int $otherUnit = null;

    #[Analytics\Measure(function: new Sum('noUnit'))]
    public ?int $noUnit = null;
}
