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

namespace Rekalogika\Analytics\Contracts\DimensionGroup;

use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

/**
 * Allows a value resolver to be used inside a dimension group. The framework
 * will call `withInput()` method with the input value resolver of the dimension
 * as defined in the summary class.
 *
 * A dimension inside a dimension group does not know where its source value
 * comes from. That is decided by the dimension in the summary class (or in the
 * parent group) that holds the group. When the framework builds the metadata,
 * it gives the holder's value resolver to the resolver of each dimension in
 * the group through this method, and the latter derives its own value from it,
 * e.g. by extracting the year from a date.
 */
interface DimensionGroupAware
{
    /**
     * Returns a copy of this value resolver that takes its input from the
     * given value resolver, which is the one defined in the dimension holding
     * the group.
     */
    public function withInput(ValueResolver $input): static;
}
