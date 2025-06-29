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

namespace Rekalogika\Analytics\Time\Filter;

use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange\DateRangeFilterOptions;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements DateRangeFilterOptions<TimeBin>
 */
final readonly class TimeBinDateRangeFilterOptions implements DateRangeFilterOptions
{
    /**
     * @param class-string<TimeBin> $timeBinClass
     */
    public function __construct(
        private string $timeBinClass,
        private TranslatableInterface $label,
        private ?TranslatableInterface $help,
    ) {}

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getHelp(): ?TranslatableInterface
    {
        return $this->help;
    }

    #[\Override]
    public function transformDateToObject(\DateTimeInterface $date): object
    {
        return ($this->timeBinClass)::createFromDateTime($date);
    }
}
