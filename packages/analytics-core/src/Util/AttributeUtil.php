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

namespace Rekalogika\Analytics\Util;

final readonly class AttributeUtil
{
    private function __construct() {}

    /**
     * @param object|class-string $objectOrClass
     * @return iterable<class-string>
     */
    private static function getAllClassesFromObject(
        object|string $objectOrClass,
    ): iterable {
        $class = \is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        yield $class;

        $parents = class_parents($class, true);

        if ($parents !== false) {
            yield from array_values($parents);
        }

        $interfaces = class_implements($class, true);

        if ($interfaces !== false) {
            yield from array_values($interfaces);
        }
    }

    /**
     * @param class-string $class
     * @return iterable<string>
     */
    public static function getPropertiesOfClass(string $class): iterable
    {
        $properties = [];
        $allClasses = self::getAllClassesFromObject($class);

        foreach ($allClasses as $class) {
            $reflectionClass = new \ReflectionClass($class);

            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $properties[$reflectionProperty->getName()] = true;
            }
        }

        yield from array_keys($properties);
    }

    /**
     * @param class-string $class
     * @param class-string $attributeClass
     */
    public static function classHasAttribute(
        string $class,
        string $attributeClass,
    ): bool {
        return self::getClassAttribute($class, $attributeClass) !== null;
    }

    /**
     * @template T of object
     * @param class-string $class
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    public static function getClassAttribute(
        string $class,
        string $attributeClass,
    ): ?object {
        $classes = self::getAllClassesFromObject($class);

        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);

            $reflectionAttributes = $reflectionClass
                ->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);

            foreach ($reflectionAttributes as $reflectionAttribute) {
                try {
                    return $reflectionAttribute->newInstance();
                } catch (\Error) {
                    // Ignore errors
                }
            }
        }

        return null;
    }

    /**
     * @template T of object
     * @param class-string $class
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    public static function getPropertyAttribute(
        string $class,
        string $property,
        string $attributeClass,
    ): ?object {
        $classes = self::getAllClassesFromObject($class);

        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);

            try {
                $reflectionProperty = $reflectionClass->getProperty($property);
            } catch (\ReflectionException) {
                continue;
            }

            $reflectionAttributes = $reflectionProperty
                ->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);

            foreach ($reflectionAttributes as $reflectionAttribute) {
                try {
                    return $reflectionAttribute->newInstance();
                } catch (\Error) {
                    // Ignore errors
                }
            }
        }

        return null;
    }
}
