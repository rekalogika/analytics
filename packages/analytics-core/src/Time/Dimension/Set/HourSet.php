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

namespace Rekalogika\Analytics\Time\Dimension\Set;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Contracts\Hierarchy\ContextAwareHierarchy;
use Rekalogika\Analytics\Core\Entity\ContextAwareHierarchyTrait;
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\Metadata\Hierarchy;
use Rekalogika\Analytics\Time\Bin\Hour;
use Rekalogika\Analytics\Time\Bin\HourOfDay;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

#[Embeddable()]
#[Hierarchy()]
class HourSet implements ContextAwareHierarchy
{
    use ContextAwareHierarchyTrait;

    #[Column(
        type: TimeBinType::TypeHour,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Hour'),
        source: new TimeBin(TimeBinType::Hour),
    )]
    private ?int $hour = null;

    #[Column(
        type: TimeBinType::TypeHourOfDay,
        nullable: true,
        enumType: HourOfDay::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Hour of Day'),
        source: new TimeBin(TimeBinType::HourOfDay),
    )]
    private ?HourOfDay $hourOfDay = null;

    public function getHour(): ?Hour
    {
        return $this->getContext()->getUserValue(
            property: 'hour',
            rawValue: $this->hour,
            class: Hour::class,
        );
    }

    public function getHourOfDay(): ?HourOfDay
    {
        return $this->getContext()->getUserValue(
            property: 'hourOfDay',
            rawValue: $this->hourOfDay,
            class: HourOfDay::class,
        );
    }
}
