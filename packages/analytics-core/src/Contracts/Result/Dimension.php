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

namespace Rekalogika\Analytics\Contracts\Result;

/**
 * Represent a dimension, together with the member of the dimension in a
 * specific cell. Each entry in a cell's coordinates is an instance of this
 * interface, e.g. the dimension `customerCountry` with the member `France`.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Dimension extends Property
{
    /**
     * The member of the dimension that this cell represents. (e.g. France,
     * 12:00). This is the value returned using normal access methods. If a
     * getter exists, it will be used instead of accessing the property
     * directly.
     */
    public function getMember(): mixed;

    /**
     * The raw member of the dimension as returned by Doctrine, bypassing
     * getters.
     */
    public function getRawMember(): mixed;

    /**
     * The member in the form suitable for display. i.e. null values are
     * replaced with the null label, which is a translatable describing the
     * absence of a value, e.g. "(none)".
     */
    public function getDisplayMember(): mixed;
}
