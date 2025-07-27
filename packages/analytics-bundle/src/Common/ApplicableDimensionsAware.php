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

namespace Rekalogika\Analytics\Bundle\Common;

interface ApplicableDimensionsAware
{
    /**
     * Iterable of arrays containing all the applicable class name and dimension
     * name. Used for services that manages a specific set of dimensions, so the
     * framework can optimize the service retrieval.
     *
     * @return iterable<ApplicableDimension>
     */
    public static function getApplicableDimensions(): iterable;
}
