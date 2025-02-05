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

namespace Rekalogika\Analytics\Query;

class SummaryItem extends SummaryField
{
    public function __construct(
        string $key,
        mixed $legend,
        mixed $name,
    ) {
        parent::__construct(
            key: $key,
            legend: $legend,
            item: $name,
            value: null,
            rawValue: null,
        );
    }
}
