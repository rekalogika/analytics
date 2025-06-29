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

/**
 * @template T of object
 */
final readonly class NumberRange implements \Stringable
{
    /**
     * @param NumberRangesFilterOptions<T> $options
     */
    public function __construct(
        private readonly string $dimension,
        private readonly NumberRangesFilterOptions $options,
        private int $start,
        private int $end,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s-%s', $this->start, $this->end);
    }

    /**
     * @return T
     */
    private function getStartObject(): object
    {
        return $this->options->transformNumberToObject($this->start);
    }

    /**
     * @return T
     */
    private function getEndObject(): object
    {
        return $this->options->transformNumberToObject($this->end);
    }

    public function createExpression(): Expression
    {
        return Criteria::expr()->andX(
            Criteria::expr()->gte($this->dimension, $this->getStartObject()),
            Criteria::expr()->lte($this->dimension, $this->getEndObject()),
        );
    }
}
