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

/**
 * Represents a bin, which is the result of a data binning operation.
 *
 * Binning groups a continuous value into discrete buckets. A time bin, e.g. a
 * month or an hour of the day, is a bin of a date and time. Bins are used as
 * dimension members. Being sequence members, they can be ordered, and the
 * framework can fill in the bins missing from a query result.
 *
 * @template T The type of the value being binned, e.g. `\DateTimeInterface`.
 */
interface Bin extends SequenceMember {}
