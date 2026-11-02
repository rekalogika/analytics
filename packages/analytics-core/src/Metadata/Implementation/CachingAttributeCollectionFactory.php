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
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Decorator that caches the attribute collections returned by another
 * factory, so each class and property is reflected only once.
 */
final readonly class CachingAttributeCollectionFactory implements AttributeCollectionFactory
{
    /**
     * Collections that cannot be serialized, e.g. because an attribute holds
     * a closure, so they cannot be stored in the cache. They are kept here
     * instead, so they are still reflected only once per process.
     *
     * @var \ArrayObject<string,AttributeCollection>
     */
    private \ArrayObject $unserializableCollections;

    /**
     * @param AttributeCollectionFactory $decorated The factory doing the
     * actual work.
     * @param CacheInterface $cache Where the collections are stored. Defaults
     * to an in-memory cache that lives as long as this object.
     */
    public function __construct(
        private AttributeCollectionFactory $decorated,
        private CacheInterface $cache = new ArrayAdapter(),
    ) {
        $this->unserializableCollections = new \ArrayObject();
    }

    /**
     * @param class-string $class
     */
    #[\Override]
    public function getClassAttributes(
        string $class,
    ): AttributeCollection {
        $cacheKey = hash('xxh128', 'attributes_class_' . $class);

        return $this->get(
            $cacheKey,
            fn(): AttributeCollection => $this->decorated->getClassAttributes($class),
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
        // `::` cannot occur in a class or property name, unlike `_`
        $cacheKey = hash('xxh128', 'attributes_property_' . $class . '::' . $property);

        return $this->get(
            $cacheKey,
            fn(): AttributeCollection => $this->decorated->getPropertyAttributes($class, $property),
        );
    }

    /**
     * @param \Closure(): AttributeCollection $factory
     */
    private function get(string $cacheKey, \Closure $factory): AttributeCollection
    {
        if (isset($this->unserializableCollections[$cacheKey])) {
            return $this->unserializableCollections[$cacheKey];
        }

        /** @var AttributeCollection */
        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item, bool &$save) use ($cacheKey, $factory): AttributeCollection {
                $collection = $factory();

                try {
                    serialize($collection);
                } catch (\Throwable) {
                    $save = false;
                    $this->unserializableCollections[$cacheKey] = $collection;
                }

                return $collection;
            },
        );
    }
}
