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

namespace Rekalogika\Analytics\Contracts\Serialization;

use Rekalogika\Analytics\Contracts\Exception\RuntimeException;

/**
 * Thrown by a `ValueSerializer` that does not handle the given dimension or
 * value, so that the next serializer in the chain can be tried. It is a
 * control-flow signal rather than an error, hence it carries no message.
 */
final class UnsupportedValue extends RuntimeException
{
    public function __construct() {}
}
