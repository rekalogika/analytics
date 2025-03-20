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

namespace Rekalogika\Analytics\Bundle\UI\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Bundle\UI\Filter;
use Rekalogika\Analytics\TimeInterval;
use Symfony\Contracts\Translation\TranslatableInterface;

final class DateRangeFilter implements Filter
{
    private ?string $rawUpperBound = null;
    private ?TimeInterval $upperBound = null;

    private ?string $rawLowerBound = null;
    private ?TimeInterval $lowerBound = null;

    /**
     * @param class-string<TimeInterval> $typeClass
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly TranslatableInterface $label,
        private readonly string $dimension,
        private readonly string $typeClass,
        private readonly array $inputArray,
    ) {}

    #[\Override]
    public function getTemplate(): string
    {
        return '@RekalogikaAnalytics/filter/date_range_filter.html.twig';
    }

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension;
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
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

    public function getStart(): ?TimeInterval
    {
        if ($this->lowerBound !== null) {
            return $this->lowerBound;
        }

        $rawStart = $this->getRawStart();

        if ($rawStart === '') {
            return null;
        }

        $dateTime = new \DateTimeImmutable($rawStart);

        return $this->lowerBound = ($this->typeClass)::createFromDateTime($dateTime);
    }

    public function getEnd(): ?TimeInterval
    {
        if ($this->upperBound !== null) {
            return $this->upperBound;
        }

        $rawEnd = $this->getRawEnd();

        if ($rawEnd === '') {
            return null;
        }

        $dateTime = new \DateTimeImmutable($rawEnd);

        return $this->upperBound = ($this->typeClass)::createFromDateTime($dateTime);
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
