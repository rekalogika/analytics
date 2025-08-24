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
use Rekalogika\Analytics\Contracts\Exception\LogicException;

final readonly class NumberRange implements \Stringable
{
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

    private function getStartDatabaseValue(): mixed
    {
        if ($this->start === null) {
            return null;
        }

        /** @psalm-suppress MixedReturnStatement */
        return $this->options->transformInputToDatabaseValue($this->start);
    }

    private function getEndDatabaseValue(): mixed
    {
        if ($this->end === null) {
            return null;
        }

        return $this->options->transformInputToDatabaseValue($this->end);
    }

    public function createExpression(): Expression
    {
        /** @psalm-suppress MixedAssignment */
        $start = $this->getStartDatabaseValue();
        /** @psalm-suppress MixedAssignment */
        $end = $this->getEndDatabaseValue();

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
            Criteria::expr()->gte($this->dimension, $this->getStartDatabaseValue()),
            Criteria::expr()->lte($this->dimension, $this->getEndDatabaseValue()),
        );
    }
}
