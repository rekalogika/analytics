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

namespace Rekalogika\Analytics\Tests\IntegrationTests\Fixtures;

use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollectionFactory;

/**
 * Replaces some attributes of a class or of its properties, to test metadata
 * that the test application cannot declare, e.g. because it is invalid.
 */
final readonly class OverridingAttributeCollectionFactory implements AttributeCollectionFactory
{
    /**
     * @param array<class-string,list<object>> $classAttributes The attributes
     * replacing the ones of the same type on the class.
     * @param array<class-string,array<string,list<object>>> $propertyAttributes
     * The attributes replacing the ones of the same type on the property.
     */
    public function __construct(
        private AttributeCollectionFactory $decorated,
        private array $classAttributes = [],
        private array $propertyAttributes = [],
    ) {}

    #[\Override]
    public function getClassAttributes(string $class): AttributeCollection
    {
        return self::override(
            $this->decorated->getClassAttributes($class),
            $this->classAttributes[$class] ?? [],
        );
    }

    #[\Override]
    public function getPropertyAttributes(string $class, string $property): AttributeCollection
    {
        return self::override(
            $this->decorated->getPropertyAttributes($class, $property),
            $this->propertyAttributes[$class][$property] ?? [],
        );
    }

    /**
     * @param list<object> $overrides
     */
    private static function override(
        AttributeCollection $attributes,
        array $overrides,
    ): AttributeCollection {
        if ($overrides === []) {
            return $attributes;
        }

        return new class ($attributes, $overrides) implements AttributeCollection {
            /**
             * @param list<object> $overrides
             */
            public function __construct(
                private readonly AttributeCollection $attributes,
                private readonly array $overrides,
            ) {}

            #[\Override]
            public function getAttribute(string $class): object
            {
                return $this->tryGetOverride($class)
                    ?? $this->attributes->getAttribute($class);
            }

            #[\Override]
            public function tryGetAttribute(string $class): ?object
            {
                return $this->tryGetOverride($class)
                    ?? $this->attributes->tryGetAttribute($class);
            }

            #[\Override]
            public function getAttributes(string $class): array
            {
                $override = $this->tryGetOverride($class);

                return $override !== null
                    ? [$override]
                    : $this->attributes->getAttributes($class);
            }

            #[\Override]
            public function hasAttribute(string $class): bool
            {
                foreach ($this->overrides as $override) {
                    if ($override instanceof $class) {
                        return true;
                    }
                }

                return $this->attributes->hasAttribute($class);
            }

            /**
             * @template T of object
             * @param class-string<T> $class
             * @return T|null
             */
            private function tryGetOverride(string $class): ?object
            {
                foreach ($this->overrides as $override) {
                    if ($override instanceof $class) {
                        return $override;
                    }
                }

                return null;
            }
        };
    }
}
