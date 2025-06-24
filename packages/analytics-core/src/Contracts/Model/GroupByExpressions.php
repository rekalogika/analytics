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

use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

/**
 * List of available fields for the group by clause in a query.
 *
 * @extends \Traversable<string,Field|FieldSet|Cube|RollUp|GroupingSet>
 */
interface GroupByExpressions extends \Traversable
{
    public function get(
        string $name,
    ): Field|FieldSet|Cube|RollUp|GroupingSet|null;
}
