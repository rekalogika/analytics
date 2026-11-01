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

/**
 * Converts the member of a dimension to a scalar identifier and back, so that
 * coordinates can be serialized, e.g. into a URL. A related entity is
 * typically serialized as its primary key, and an enum as its backing value.
 *
 * Implement this interface to handle member types that the default
 * implementation does not support. Under Symfony, implementations are
 * registered automatically and tried in turn until one does not throw
 * `UnsupportedValue`.
 *
 * @see CoordinatesMapper
 */
interface ValueSerializer
{
    /**
     * Converts a dimension member to its scalar identifier.
     *
     * @param class-string $class The summary entity class name.
     * @param string $dimension The name of the dimension.
     * @param mixed $value The raw member value, as stored in the summary
     * table.
     * @return int|string|null The serialized value.
     * @throws UnsupportedValue If the serializer does not support the value
     * type.
     * @throws InvalidArgumentException If the value cannot be serialized.
     */
    public function serialize(
        string $class,
        string $dimension,
        mixed $value,
    ): int|string|null;

    /**
     * Converts a scalar identifier back to the dimension member it was
     * serialized from.
     *
     * @param class-string $class The summary entity class name.
     * @param string $dimension The name of the dimension.
     * @param int|string|null $identifier The serialized value, as returned by
     * `serialize()`.
     * @return mixed The deserialized value.
     * @throws UnsupportedValue If the serializer does not support the value
     * type.
     * @throws InvalidArgumentException If the value cannot be deserialized.
     */
    public function deserialize(
        string $class,
        string $dimension,
        int|string|null $identifier,
    ): mixed;
}
