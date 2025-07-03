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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Common\Exception\LogicException;

/**
 * @template T of object
 */
final readonly class NumberRange implements \Stringable
{
    /**
     * @template U of object
     * @param NumberRangesFilterOptions<U> $options
     * @return self<U>
     */
    public static function create(
        string $dimension,
        NumberRangesFilterOptions $options,
        string $input,
    ): ?self {
        [$start, $end] = explode('-', $input);

        if (is_numeric($start)) {
            $start = (int) $start;
        } else {
            $start = null;
        }

        if (is_numeric($end)) {
            $end = (int) $end;
        } else {
            $end = null;
        }

        if ($start === null && $end === null) {
            return null;
        }

        return new self(
            dimension: $dimension,
            options: $options,
            start: $start,
            end: $end,
        );
    }

    /**
     * @param NumberRangesFilterOptions<T> $options
     */
    private function __construct(
        private readonly string $dimension,
        private readonly NumberRangesFilterOptions $options,
        private ?int $start,
        private ?int $end,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s-%s', $this->start ?? '', $this->end ?? '');
    }

    /**
     * @return ?T
     */
    private function getStartObject(): ?object
    {
        if ($this->start === null) {
            return null;
        }

        return $this->options->transformNumberToObject($this->start);
    }

    /**
     * @return ?T
     */
    private function getEndObject(): ?object
    {
        if ($this->end === null) {
            return null;
        }

        return $this->options->transformNumberToObject($this->end);
    }

    public function createExpression(): Expression
    {
        $start = $this->getStartObject();
        $end = $this->getEndObject();

        if ($start === null && $end === null) {
            throw new LogicException('Both start and end cannot be null');
        }

        if ($start === null) {
            return Criteria::expr()->lte($this->dimension, $end);
        }

        if ($end === null) {
            return Criteria::expr()->gte($this->dimension, $start);
        }

        return Criteria::expr()->andX(
            Criteria::expr()->gte($this->dimension, $this->getStartObject()),
            Criteria::expr()->lte($this->dimension, $this->getEndObject()),
        );
    }
}
