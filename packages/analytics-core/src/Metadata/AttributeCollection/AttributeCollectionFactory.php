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

interface AttributeCollectionFactory
{
    /**
     * @param class-string $class
     */
    public function getClassAttributes(
        string $class,
    ): AttributeCollection;

    /**
     * @param class-string $class
     */
    public function getPropertyAttributes(
        string $class,
        string $property,
    ): AttributeCollection;
}
