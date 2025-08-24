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

namespace Rekalogika\Analytics\Metadata\Util;

final readonly class AttributeUtil
{
    private function __construct() {}

    /**
     * @param object|class-string $objectOrClass
     * @return iterable<class-string>
     */
    public static function getAllClassesFromObject(
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
     * @return iterable<\ReflectionProperty>
     */
    public static function getPropertiesOfClass(string $class): iterable
    {
        $properties = [];
        $allClasses = self::getAllClassesFromObject($class);

        foreach ($allClasses as $class) {
            $reflectionClass = new \ReflectionClass($class);

            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                if (isset($properties[$reflectionProperty->getName()])) {
                    continue; // Skip if property already exists
                }

                $properties[$reflectionProperty->getName()] = $reflectionProperty;
            }
        }

        yield from array_values($properties);
    }

    /**
     * @return class-string|null
     */
    public static function getTypeClassFromReflection(
        \ReflectionProperty $reflectionProperty,
    ): ?string {
        $type = $reflectionProperty->getType();

        if ($type === null) {
            return null;
        }

        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (!class_exists($name)) {
            return null;
        }

        return $name;
    }

    /**
     * @param class-string $class
     * @return class-string|null
     */
    public static function getTypeClass(
        string $class,
        string $property,
    ): ?string {
        foreach (self::getAllClassesFromObject($class) as $class) {
            $reflectionClass = new \ReflectionClass($class);

            try {
                $reflectionProperty = $reflectionClass->getProperty($property);
            } catch (\ReflectionException) {
                continue;
            }

            return self::getTypeClassFromReflection($reflectionProperty);
        }

        return null;
    }


    /**
     * @param class-string $class
     * @return iterable<object>
     */
    public static function getClassAttributes(string $class): iterable
    {
        foreach (self::getAllClassesFromObject($class) as $class) {
            $reflectionClass = new \ReflectionClass($class);
            $reflectionAttributes = $reflectionClass->getAttributes();

            foreach ($reflectionAttributes as $reflectionAttribute) {
                try {
                    yield $reflectionAttribute->newInstance();
                } catch (\Error) {
                    // Ignore errors.
                }
            }
        }
    }

    /**
     * @param class-string $class
     * @param string $property
     * @return iterable<object>
     */
    public static function getPropertyAttributes(
        string $class,
        string $property,
    ): iterable {
        foreach (self::getAllClassesFromObject($class) as $class) {
            $reflectionClass = new \ReflectionClass($class);

            try {
                $reflectionProperty = $reflectionClass->getProperty($property);
            } catch (\ReflectionException) {
                continue;
            }

            $reflectionAttributes = $reflectionProperty->getAttributes();

            foreach ($reflectionAttributes as $reflectionAttribute) {
                try {
                    yield $reflectionAttribute->newInstance();
                } catch (\Error) {
                    // Ignore errors.
                }
            }
        }
    }
}
