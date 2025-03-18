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

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Represent a measure within a tuple.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface MeasureMember extends TranslatableInterface
{
    public function getMeasureProperty(): string;
}
