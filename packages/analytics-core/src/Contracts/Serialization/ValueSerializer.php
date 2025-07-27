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

interface ValueSerializer
{
    /**
     * @param class-string $class The summary entity class name.
     * @return string|UnsupportedValue The serialized value. If the value is not
     * supported, returns UnsupportedValue.
     */
    public function serialize(
        string $class,
        string $dimension,
        mixed $value,
    ): string|UnsupportedValue;

    /**
     * @param class-string $class The summary entity class name.
     * @return mixed The deserialized value. If the value is not supported,
     * returns UnsupportedValue.
     */
    public function deserialize(
        string $class,
        string $dimension,
        string $identifier,
    ): mixed;
}
