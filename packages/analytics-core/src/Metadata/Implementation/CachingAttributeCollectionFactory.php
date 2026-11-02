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
final class CachingAttributeCollectionFactory implements AttributeCollectionFactory
{
    /**
     * The collections already fetched, so the cache is consulted only once
     * per process for each. This also covers collections the cache fails to
     * store, e.g. because an attribute holds a closure, which would otherwise
     * be reflected again on every call.
     *
     * @var array<string,AttributeCollection>
     */
    private array $collections = [];

    /**
     * @param AttributeCollectionFactory $decorated The factory doing the
     * actual work.
     * @param CacheInterface $cache Where the collections are stored. Defaults
     * to an in-memory cache that lives as long as this object.
     */
    public function __construct(
        private readonly AttributeCollectionFactory $decorated,
        private readonly CacheInterface $cache = new ArrayAdapter(),
    ) {}

    /**
     * @param class-string $class
     */
    #[\Override]
    public function getClassAttributes(
        string $class,
    ): AttributeCollection {
        $cacheKey = hash('xxh128', 'attributes_class_' . $class);

        return $this->collections[$cacheKey] ??= $this->cache->get(
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
        // `::` cannot occur in a class or property name, unlike `_`
        $cacheKey = hash('xxh128', 'attributes_property_' . $class . '::' . $property);

        return $this->collections[$cacheKey] ??= $this->cache->get(
            $cacheKey,
            function () use ($class, $property) {
                return $this->decorated->getPropertyAttributes($class, $property);
            },
        );
    }
}
