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
 * Represent a measure within a coordinates. An implementation of this interface
 * will be the member of a dimension.
 *
 * The special `@values` dimension treats the measures of a summary as if they
 * were the members of a dimension. This lets the measures be laid out like
 * any other dimension, e.g. as the columns of a pivot table. Each member of
 * `@values` is an instance of this interface, identifying one measure.
 * Translating the member gives the label of the measure.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface MeasureMember extends TranslatableInterface
{
    /**
     * The name of the measure property in the summary class.
     */
    public function getMeasureProperty(): string;
}
