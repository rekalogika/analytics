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
 * Reads the PHP attributes of classes and properties. The attributes of
 * parent classes and interfaces are included, so a summary class inherits the
 * attributes of its superclass. Implementations may cache the results.
 */
interface AttributeCollectionFactory
{
    /**
     * Returns the attributes of the class, including those of its parents and
     * interfaces.
     *
     * @param class-string $class
     */
    public function getClassAttributes(
        string $class,
    ): AttributeCollection;

    /**
     * Returns the attributes of the property, including those of the same
     * property in the parents of the class.
     *
     * @param class-string $class
     */
    public function getPropertyAttributes(
        string $class,
        string $property,
    ): AttributeCollection;
}
