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

namespace Rekalogika\Analytics\Bundle\UI\Model;

use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Symfony\Contracts\Translation\TranslatableInterface;

interface FilterExpression
{
    public function getLabel(): TranslatableInterface;

    public function applyToQuery(SummaryQuery $query): void;
}
