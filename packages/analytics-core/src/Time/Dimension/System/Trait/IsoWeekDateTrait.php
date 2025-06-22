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

namespace Rekalogika\Analytics\Time\Dimension\System\Trait;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Core\Entity\ContextAwareHierarchyTrait;
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\ValueResolver\Noop;
use Rekalogika\Analytics\Time\Bin\WeekYear;
use Rekalogika\Analytics\Time\Dimension\Set\WeekDateSet;
use Rekalogika\Analytics\Time\Dimension\Set\WeekSet;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

trait IsoWeekDateTrait
{
    use ContextAwareHierarchyTrait;

    #[Column(
        type: TimeBinType::TypeWeekYear,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week Year'),
        source: new TimeBin(TimeBinType::WeekYear),
    )]
    private ?int $weekYear = null;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Week'),
        source: new Noop(),
    )]
    private ?WeekSet $week = null;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Week Date'),
        source: new Noop(),
    )]
    private ?WeekDateSet $weekDate = null;

    public function getWeekYear(): ?WeekYear
    {
        return $this->getContext()->getUserValue(
            property: 'weekYear',
            rawValue: $this->weekYear,
            class: WeekYear::class,
        );
    }

    public function getWeek(): ?WeekSet
    {
        return $this->week;
    }

    public function getWeekDate(): ?WeekDateSet
    {
        return $this->weekDate;
    }
}
