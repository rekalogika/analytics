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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

interface ValueSerializer
{
    /**
     * @param class-string $class The summary entity class name.
     * @return string The serialized value.
     * @throws UnsupportedValue If the serializer does not support the value
     * type.
     * @throws InvalidArgumentException If the value cannot be serialized.
     */
    public function serialize(
        string $class,
        string $dimension,
        mixed $value,
    ): string;

    /**
     * @param class-string $class The summary entity class name.
     * @return mixed The deserialized value.
     * @throws UnsupportedValue If the serializer does not support the value
     * type.
     * @throws InvalidArgumentException If the value cannot be deserialized.
     */
    public function deserialize(
        string $class,
        string $dimension,
        string $identifier,
    ): mixed;
}
