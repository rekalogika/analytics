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
    private float $divider;

    /**
     * @param numeric $divider
     */
    public function __construct(
        int|float|string $divider,
    ) {
        $this->divider = (float) $divider;

        if ($this->divider === 0.0) {
            throw new \InvalidArgumentException('Divider cannot be zero.');
        }
    }

    #[\Override]
    public function resolveNumericValue(mixed $value, mixed $rawValue): int|float
    {
        if (is_numeric($value)) {
            return (float) $value / $this->divider;
        }

        if (is_numeric($rawValue)) {
            return (float) $rawValue / $this->divider;
        }

        return 0;
    }
}
