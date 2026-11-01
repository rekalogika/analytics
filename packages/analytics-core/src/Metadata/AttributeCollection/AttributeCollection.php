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

namespace Rekalogika\Analytics\Metadata\AttributeCollection;

/**
 * The PHP attributes found on a class or on a property, looked up by type.
 * Lookups match subclasses and implemented interfaces too, so an attribute
 * can be requested by any of its parent classes or interfaces.
 *
 * Custom attributes placed on summary classes and their properties are
 * available through this interface in the metadata, so other code can read
 * them without reflection.
 */
interface AttributeCollection
{
    /**
     * Returns the first attribute of the given type.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws \Rekalogika\Analytics\Contracts\Exception\RuntimeException If
     * there is no such attribute.
     */
    public function getAttribute(string $class): object;

    /**
     * Returns the first attribute of the given type, or null if there is none.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return ?T
     */
    public function tryGetAttribute(string $class): ?object;

    /**
     * Returns all the attributes of the given type.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return list<T>
     */
    public function getAttributes(string $class): array;

    /**
     * Returns true if there is at least one attribute of the given type.
     */
    public function hasAttribute(string $class): bool;
}
