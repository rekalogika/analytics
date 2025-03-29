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

namespace Rekalogika\Analytics\Contracts\Summary;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

/**
 * A hack to work around Doctrine ORM QueryBuilder setParameter with an array
 * argument and the property having a custom Doctrine type.
 *
 * This is mainly for the IN expression. We don't want to unroll the array in
 * the query and convert it into OR statements because it may affect how the
 * database optimizes the query.
 *
 * @internal
 */
interface ParameterTypeAware
{
    public function getArrayParameterType(): ParameterType|ArrayParameterType|string|int|null;
}
