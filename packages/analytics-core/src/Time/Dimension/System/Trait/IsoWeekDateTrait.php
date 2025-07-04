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
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\ValueResolver\Noop;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekYear;
use Rekalogika\Analytics\Time\Dimension\Set\IsoWeekDateSet;
use Rekalogika\Analytics\Time\Dimension\Set\IsoWeekWeekSet;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

trait IsoWeekDateTrait
{
    #[Column(
        type: IsoWeekYear::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week Year'),
        source: new TimeBinValueResolver(IsoWeekYear::class),
    )]
    private ?int $weekYear = null;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Week'),
        source: new Noop(),
    )]
    private ?IsoWeekWeekSet $week = null;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Week Date'),
        source: new Noop(),
    )]
    private ?IsoWeekDateSet $weekDate = null;

    public function getWeekYear(): ?IsoWeekYear
    {
        return $this->getContext()->getUserValue(
            property: 'weekYear',
            class: IsoWeekYear::class,
        );
    }

    public function getWeek(): ?IsoWeekWeekSet
    {
        return $this->week;
    }

    public function getWeekDate(): ?IsoWeekDateSet
    {
        return $this->weekDate;
    }
}
