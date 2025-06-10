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

namespace Rekalogika\Analytics\Contracts\Hierarchy;

use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

/**
 * Allows a value resolver to be used inside a hierarchy. The framework will
 * call `withInput()` method with the input value resolver of the dimension as
 * defined in the summary class.
 */
interface HierarchyAware
{
    /**
     * Sets the input value resolver defined in the dimension.
     */
    public function withInput(ValueResolver $input): static;
}
