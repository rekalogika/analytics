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

namespace Rekalogika\Analytics\PivotTable\Model\ResultSet;

use Rekalogika\Analytics\PivotTable\Model\Property;

abstract readonly class ResultSetProperty implements Property
{
    final public function __construct(private mixed $value) {}

    #[\Override]
    public function getContent(): mixed
    {
        return $this->value;
    }
}
