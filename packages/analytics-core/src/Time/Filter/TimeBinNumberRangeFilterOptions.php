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

use Rekalogika\Analytics\Time\RecurringTimeBin;
use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges\NumberRangesFilterOptions;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements NumberRangesFilterOptions<TimeBin|RecurringTimeBin>
 */
final readonly class TimeBinNumberRangeFilterOptions implements NumberRangesFilterOptions
{
    /**
     * @param class-string<TimeBin|RecurringTimeBin> $timeBinClass
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
    public function transformNumberToObject(int $number): object
    {
        return ($this->timeBinClass)::createFromDatabaseValue($number);
    }
}
