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
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Decorator that caches the attribute collections returned by another
 * factory, so each class and property is reflected only once.
 */
final readonly class CachingAttributeCollectionFactory implements AttributeCollectionFactory
{
    /**
     * @param AttributeCollectionFactory $decorated The factory doing the
     * actual work.
     * @param CacheInterface $cache Where the collections are stored. Defaults
     * to an in-memory cache that lives as long as this object.
     */
    public function __construct(
        private AttributeCollectionFactory $decorated,
        private CacheInterface $cache = new ArrayAdapter(),
    ) {}

    /**
     * @param class-string $class
     */
    #[\Override]
    public function getClassAttributes(
        string $class,
    ): AttributeCollection {
        $cacheKey = hash('xxh128', 'attributes_class_' . $class);

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
        $cacheKey = hash('xxh128', 'attributes_property_' . $class . '_' . $property);

        return $this->cache->get(
            $cacheKey,
            function () use ($class, $property) {
                return $this->decorated->getPropertyAttributes($class, $property);
            },
        );
    }
}
