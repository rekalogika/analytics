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
use Rekalogika\Analytics\Time\Bin\Year;
use Rekalogika\Analytics\Time\Dimension\Set\DateSet;
use Rekalogika\Analytics\Time\Dimension\Set\MonthSet;
use Rekalogika\Analytics\Time\Dimension\Set\QuarterSet;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

trait GregorianDateTrait
{
    #[Column(
        type: TimeBinType::TypeYear,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Year'),
        source: new TimeBinValueResolver(TimeBinType::Year),
    )]
    private ?int $year = null;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Quarter'),
        source: new Noop(),
    )]
    private ?QuarterSet $quarter = null;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Month'),
        source: new Noop(),
    )]
    private ?MonthSet $month = null;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Date'),
        source: new Noop(),
    )]
    private ?DateSet $date = null;

    public function getYear(): ?Year
    {
        return $this->getContext()->getUserValue(
            property: 'year',
            class: Year::class,
        );
    }

    public function getQuarter(): ?QuarterSet
    {
        return $this->quarter;
    }

    public function getMonth(): ?MonthSet
    {
        return $this->month;
    }

    public function getDate(): ?DateSet
    {
        return $this->date;
    }
}
