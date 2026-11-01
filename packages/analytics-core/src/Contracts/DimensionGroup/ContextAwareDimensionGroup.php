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

use Rekalogika\Analytics\Contracts\Context\DimensionGroupContext;

/**
 * A dimension group is an embeddable object holding several related
 * dimensions, e.g. the year, month, and day of a date. If a dimension group
 * implements this interface, the framework calls `setContext()` right after
 * populating the object from a row of the query result, before any of its
 * getters are called.
 *
 * The context lets the getters of the group return user-facing values
 * computed from the raw values stored in the properties. Use
 * `ContextAwareDimensionGroupTrait` for a ready-made implementation of this
 * interface.
 *
 * @see DimensionGroupContext
 */
interface ContextAwareDimensionGroup
{
    /**
     * Receives the context from the framework.
     */
    public function setContext(DimensionGroupContext $context): void;
}
