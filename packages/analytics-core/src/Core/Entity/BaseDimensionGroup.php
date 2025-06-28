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

namespace Rekalogika\Analytics\Core\Entity;

use Rekalogika\Analytics\Contracts\DimensionGroup\ContextAwareDimensionGroup;

/**
 * Super class for a dimension group.
 */
abstract class BaseDimensionGroup implements ContextAwareDimensionGroup
{
    use ContextAwareDimensionGroupTrait;
}
