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

namespace Rekalogika\Analytics\Time;

use Rekalogika\Analytics\Contracts\Model\Bin;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Represents a time bin that is recurring. For example: month of the year, it
 * groups the data by month, so that all data in January will be grouped into
 * January bin, all data in February will be grouped into February bin, and so
 * on. It will include all Februarys in all years, so that February 2020 and
 * February 2021 will be grouped into the same bin.
 */
interface RecurringTimeBin extends TranslatableInterface, TimeBin, \BackedEnum {}
