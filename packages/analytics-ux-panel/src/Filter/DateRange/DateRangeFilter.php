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
use Rekalogika\Analytics\Time\Bin\Date;
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

    public function getRawStart(): string
    {
        if ($this->rawLowerBound !== null) {
            return $this->rawLowerBound;
        }

        /** @psalm-suppress MixedAssignment */
        $string = $this->inputArray['start'] ?? null;

        if (!\is_string($string)) {
            $string = '';
        }

        return $this->rawLowerBound = $string;
    }

    public function getRawEnd(): string
    {
        if ($this->rawUpperBound !== null) {
            return $this->rawUpperBound;
        }

        /** @psalm-suppress MixedAssignment */
        $string = $this->inputArray['end'] ?? null;

        if (!\is_string($string)) {
            $string = '';
        }

        return $this->rawUpperBound = $string;
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

        $rawStart = $this->getRawStart();

        if ($rawStart === '') {
            return null;
        }

        return $this->lowerBound = $this->options
            ->transformDateToObject(new \DateTimeImmutable($rawStart));
    }

    /**
     * @return ?T
     */
    public function getEnd(): ?object
    {
        if ($this->upperBound !== null) {
            return $this->upperBound;
        }

        $rawEnd = $this->getRawEnd();

        if ($rawEnd === '') {
            return null;
        }

        return $this->upperBound = $this->options
            ->transformDateToObject(new \DateTimeImmutable($rawEnd));
    }

    #[\Override]
    public function createExpression(): ?Expression
    {
        $start = $this->getStart();
        $end = $this->getEnd();

        if ($start === null || $end === null) {
            return null;
        }

        return Criteria::expr()->andX(
            Criteria::expr()->gte($this->dimension, $this->getStart()),
            Criteria::expr()->lte($this->dimension, $this->getEnd()),
        );
    }
}
