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

namespace Rekalogika\Analytics\PivotTable\Model;

/**
 * Represents a property of a element in a pivot table. It can be a label,
 * value, or dimension member. It wraps the element so that the final renderer
 * can know if the element is a label, value, or member.
 */
interface Property
{
    public function getContent(): mixed;
}
