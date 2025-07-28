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

namespace Rekalogika\Analytics\Metadata\Implementation;

use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Util\AttributeUtil;

final readonly class DefaultAttributeCollectionFactory implements AttributeCollectionFactory
{
    /**
     * @param class-string $class
     */
    #[\Override]
    public function getClassAttributes(
        string $class,
    ): AttributeCollection {
        $attributes = AttributeUtil::getClassAttributes($class);

        return new DefaultAttributeCollection($attributes);
    }

    /**
     * @param class-string $class
     */
    #[\Override]
    public function getPropertyAttributes(
        string $class,
        string $property,
    ): AttributeCollection {
        $attributes = AttributeUtil::getPropertyAttributes($class, $property);

        return new DefaultAttributeCollection($attributes);
    }
}
