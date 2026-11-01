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

namespace Rekalogika\Analytics\Contracts\Model;

/**
 * Provides information about the raw Doctrine value of this object. Used by the
 * framework for query and comparison.
 *
 * Implemented by objects used as dimension members, such as bins, whose
 * representation in the summary table differs from the object itself. The
 * framework uses the database value when the object appears in a dice
 * predicate, and when matching the object against a stored member.
 *
 * @template T The type of the value as stored in the database.
 */
interface DatabaseValueAware
{
    /**
     * The value of this object as stored in the summary table.
     *
     * @return T
     */
    public function getDatabaseValue(): mixed;
}
