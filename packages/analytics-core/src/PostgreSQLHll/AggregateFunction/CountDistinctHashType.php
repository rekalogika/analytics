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

namespace Rekalogika\Analytics\PostgreSQLHll\AggregateFunction;

/**
 * @see https://github.com/citusdata/postgresql-hll#hashing
 */
enum CountDistinctHashType: string
{
    case Boolean = 'boolean';
    case Smallint = 'smallint';
    case Integer = 'integer';
    case Bigint = 'bigint';
    case Bytea = 'bytea';
    case Text = 'text';
    case Any = 'any';
}
