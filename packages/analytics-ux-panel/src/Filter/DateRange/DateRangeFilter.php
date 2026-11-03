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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Time\Bin\Gregorian\Date;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * DateRangeFilter is a filter that allows users to select a range of dates.
 * Implemented using flatpickr date range picker.
 *
 * @template T of object
 */
final class DateRangeFilter implements Filter
{
    /**
     * The date format of the input, the default format of flatpickr.
     */
    private const FORMAT = 'Y-m-d';

    private ?string $rawUpperBound = null;

    /**
     * @var T|null
     */
    private ?object $upperBound = null;

    private ?string $rawLowerBound = null;

    /**
     * @var T|null
     */
    private ?object $lowerBound = null;

    /**
     * @param DateRangeFilterOptions<T> $options
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly DateRangeFilterOptions $options,
        private readonly string $dimension,
        private readonly array $inputArray,
    ) {}

    #[\Override]
    public function getTemplate(): string
    {
        return '@RekalogikaAnalyticsUXPanel/filter/date_range_filter.html.twig';
    }

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension;
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->options->getLabel();
    }

    /**
     * Returns the date from the input array, or null if it is missing or not
     * a valid date in the expected format.
     */
    private function getInputDate(string $key): ?\DateTimeImmutable
    {
        /** @psalm-suppress MixedAssignment */
        $string = $this->inputArray[$key] ?? null;

        if (!\is_string($string)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!' . self::FORMAT, $string);

        // reject overflowing dates, e.g. 2024-02-30
        if ($date === false || $date->format(self::FORMAT) !== $string) {
            return null;
        }

        return $date;
    }

    public function getRawStart(): string
    {
        if ($this->rawLowerBound !== null) {
            return $this->rawLowerBound;
        }

        return $this->rawLowerBound = $this->getInputDate('start')
            ?->format(self::FORMAT) ?? '';
    }

    public function getRawEnd(): string
    {
        if ($this->rawUpperBound !== null) {
            return $this->rawUpperBound;
        }

        return $this->rawUpperBound = $this->getInputDate('end')
            ?->format(self::FORMAT) ?? '';
    }

    public function getRawValue(): string
    {
        $start = $this->getRawStart();
        $end = $this->getRawEnd();

        if ($start === '') {
            return '';
        }

        if ($end === '') {
            return $start;
        }

        return \sprintf('%s - %s', $start, $end);
    }

    /**
     * @return ?T
     */
    public function getStart(): ?object
    {
        if ($this->lowerBound !== null) {
            return $this->lowerBound;
        }

        $date = $this->getInputDate('start');

        if ($date === null) {
            return null;
        }

        return $this->lowerBound = $this->options
            ->transformDateToObject($date);
    }

    /**
     * @return ?T
     */
    public function getEnd(): ?object
    {
        if ($this->upperBound !== null) {
            return $this->upperBound;
        }

        $date = $this->getInputDate('end');

        if ($date === null) {
            return null;
        }

        return $this->upperBound = $this->options
            ->transformDateToObject($date);
    }

    #[\Override]
    public function createExpression(): ?Expression
    {
        $start = $this->getStart();
        $end = $this->getEnd();

        // one-sided ranges are applied as a single bound
        if ($start === null && $end === null) {
            return null;
        }

        if ($end === null) {
            return Criteria::expr()->gte($this->dimension, $start);
        }

        if ($start === null) {
            return Criteria::expr()->lte($this->dimension, $end);
        }

        return Criteria::expr()->andX(
            Criteria::expr()->gte($this->dimension, $start),
            Criteria::expr()->lte($this->dimension, $end),
        );
    }
}
