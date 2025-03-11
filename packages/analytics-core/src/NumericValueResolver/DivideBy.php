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

namespace Rekalogika\Analytics\NumericValueResolver;

use Rekalogika\Analytics\NumericValueResolver;

final readonly class DivideBy implements NumericValueResolver
{
    /**
     * @param int|float $divider
     */
    public function __construct(
        private int|float $divider,
    ) {
        if (0 === $this->divider || 0.0 === $this->divider) {
            throw new \InvalidArgumentException('Divider cannot be zero.');
        }
    }

    #[\Override]
    public function resolveNumericValue(mixed $value, mixed $rawValue): int|float
    {
        if (is_numeric($value)) {
            return (float) $value / (float) $this->divider;
        }

        if (is_numeric($rawValue)) {
            return (float) $rawValue / (float) $this->divider;
        }

        return 0;
    }
}
