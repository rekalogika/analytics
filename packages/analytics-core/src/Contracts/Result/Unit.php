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

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Represent a unit of measurement, e.g. a currency or a count. Translating the
 * unit gives its label.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Unit extends TranslatableInterface
{
    /**
     * The unit signature. Two units with the same signature are considered
     * identical, e.g. two measures with the same unit signature can share an
     * axis in a chart.
     */
    public function getSignature(): string;
}
