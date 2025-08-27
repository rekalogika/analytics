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

namespace Rekalogika\Analytics\Contracts\Result;

use Rekalogika\Analytics\Contracts\Collection\OrderedMapCollection;

/**
 * An ordered coordinates of dimensions. A collection of dimensions that
 * identifies a unique intersection of members from different dimensions in the
 * cube. An ordered coordinates is ordered. The members must be from unique
 * dimensions from the same summary class.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends OrderedMapCollection<string,Dimension>
 */
interface OrderedCoordinates extends Coordinates, OrderedMapCollection {}
