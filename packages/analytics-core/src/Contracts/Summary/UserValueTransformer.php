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

namespace Rekalogika\Analytics\Contracts\Summary;

use Rekalogika\Analytics\Contracts\Context\ValueTransformerContext;

/**
 * If a value resolver implements this interface, it has the capability to
 * transform the raw value (as returned by Doctrine), into a canonical value
 * suitable for the caller.
 *
 * This is used in data binning, where a resulting integer value is converted
 * into a `Bin` instance. It is done like this so the user does not have to: 1.
 * create a custom Doctrine type every time they want to bin data, and 2.they
 * don't have to repeat the logic in the getter over and over again.
 *
 * @template I
 * @template O
 */
interface UserValueTransformer
{
    /**
     * Takes a raw value as returned by Doctrine, and transforms it into a value
     * suitable for the user.
     *
     * @param I $rawValue The raw value as returned by Doctrine.
     * @param ValueTransformerContext $context The context for getting the user
     * value.
     * @return O The final user value.
     */
    public function getUserValue(
        mixed $rawValue,
        ValueTransformerContext $context,
    ): mixed;
}
