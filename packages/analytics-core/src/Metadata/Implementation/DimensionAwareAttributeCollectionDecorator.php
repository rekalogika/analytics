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

use Rekalogika\Analytics\Contracts\Exception\RuntimeException;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;

/**
 * Attribute collection of a dimension inside a dimension group, that falls
 * back to the attributes of the parent dimension when the dimension itself
 * does not have the requested attribute. This lets an attribute placed on the
 * property holding the group apply to every dimension inside it, e.g. a time
 * zone setting on a time dimension applying to its year, month, and day.
 */
final readonly class DimensionAwareAttributeCollectionDecorator implements
    AttributeCollection
{
    /**
     * @param AttributeCollection $decorated The attributes of the dimension
     * itself.
     * @param DimensionMetadata $dimensionMetadata The parent dimension, whose
     * attributes are used as the fallback.
     */
    public function __construct(
        private AttributeCollection $decorated,
        private DimensionMetadata $dimensionMetadata,
    ) {}

    #[\Override]
    public function getAttribute(string $class): object
    {
        return $this->tryGetAttribute($class) ?? throw new RuntimeException(
            "Attribute of class '$class' not found in collection.",
        );
    }

    #[\Override]
    public function tryGetAttribute(string $class): ?object
    {
        return $this->decorated->tryGetAttribute($class)
            ?? $this->dimensionMetadata
            ->getAttributes()
            ->tryGetAttribute($class);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return list<T>
     */
    #[\Override]
    public function getAttributes(string $class): array
    {
        $result = $this->decorated->getAttributes($class);

        if ($result !== []) {
            return $result;
        }

        return $this->dimensionMetadata
            ->getAttributes()
            ->getAttributes($class);
    }

    #[\Override]
    public function hasAttribute(string $class): bool
    {
        $hasAttribute = $this->decorated->hasAttribute($class);

        if ($hasAttribute) {
            return true;
        }

        return $this->dimensionMetadata
            ->getAttributes()
            ->hasAttribute($class);
    }
}
