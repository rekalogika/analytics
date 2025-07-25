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
final readonly class Number implements \Stringable
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
        if (!is_numeric($input)) {
            return null;
        }

        $input = (int) $input;

        return new self(
            dimension: $dimension,
            options: $options,
            number: $input,
        );
    }

    /**
     * @param NumberRangesFilterOptions<T> $options
     */
    private function __construct(
        private string $dimension,
        private NumberRangesFilterOptions $options,
        private int $number,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->number;
    }

    /**
     * @return T
     */
    private function getObject(): object
    {
        return $this->options->transformNumberToObject($this->number);
    }

    public function createExpression(): Expression
    {
        return Criteria::expr()->eq($this->dimension, $this->getObject());
    }
}
