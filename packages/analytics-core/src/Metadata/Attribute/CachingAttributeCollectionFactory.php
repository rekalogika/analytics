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

use Rekalogika\Analytics\Metadata\AttributeCollectionFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final readonly class CachingAttributeCollectionFactory implements AttributeCollectionFactory
{
    public function __construct(
        private AttributeCollectionFactory $decorated,
        private ArrayAdapter $cache = new ArrayAdapter(),
    ) {}

    /**
     * @param class-string $class
     */
    #[\Override]
    public function getClassAttributes(
        string $class,
    ): AttributeCollection {
        $cacheKey = 'attributes_class_' . $class;

        return $this->cache->get(
            $cacheKey,
            function () use ($class) {
                return $this->decorated->getClassAttributes($class);
            },
        );
    }

    /**
     * @param class-string $class
     */
    #[\Override]
    public function getPropertyAttributes(
        string $class,
        string $property,
    ): AttributeCollection {
        $cacheKey = 'attributes_property_' . $class . '_' . $property;

        return $this->cache->get(
            $cacheKey,
            function () use ($class, $property) {
                return $this->decorated->getPropertyAttributes($class, $property);
            },
        );
    }
}
