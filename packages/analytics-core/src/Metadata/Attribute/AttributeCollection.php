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

namespace Rekalogika\Analytics\Metadata\Attribute;

interface AttributeCollection
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function getAttribute(string $class): object;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ?T
     */
    public function tryGetAttribute(string $class): ?object;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return list<T>
     */
    public function getAttributes(string $class): array;

    public function hasAttribute(string $class): bool;
}
