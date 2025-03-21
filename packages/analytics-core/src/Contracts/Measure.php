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

namespace Rekalogika\Analytics\Contracts;

/**
 * Represent a measure
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Measure extends Property
{
    /**
     * The canonical value of the measure, as returned by normal access methods.
     * If a getter exists, it will be used to fetch the value instead of
     * accessing the property directly.
     */
    public function getValue(): mixed;

    /**
     * The value returned by Doctrine.
     */
    public function getRawValue(): mixed;

    /**
     * The value's unit of measurement. This is useful for formatting, or used
     * by charting libraries to determine if different measures can be plotted
     * on the same axis.
     */
    public function getUnit(): ?Unit;
}
