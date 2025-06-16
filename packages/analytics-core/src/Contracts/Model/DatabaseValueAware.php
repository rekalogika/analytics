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
 * @template T
 */
interface DatabaseValueAware
{
    /**
     * @return T
     */
    public function getDatabaseValue(): mixed;
}
