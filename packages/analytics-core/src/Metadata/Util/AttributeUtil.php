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

/**
 * Reflection helpers for reading attributes and property types, taking class
 * inheritance into account.
 */
final readonly class AttributeUtil
{
    private function __construct() {}

    /**
     * Returns the class of the object, followed by all of its parent classes
     * and all the interfaces it implements.
     *
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
     * Returns the properties of the class, including the private properties
     * declared in its parent classes, which plain reflection does not return.
     * A property redeclared in a subclass is returned once, from the subclass.
     *
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
     * Returns the class, interface, or enum named in the type declaration of
     * the property, or null if the property is untyped, has a built-in type
     * such as `int`, has a union or intersection type, or names a class that
     * does not exist. A nullable type such as `?Country` returns the class.
     *
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

        if (!class_exists($name) && !interface_exists($name) && !enum_exists($name)) {
            return null;
        }

        return $name;
    }

    /**
     * Like `getTypeClassFromReflection()`, but looks the property up by name,
     * searching the parent classes if it is not declared in the class itself.
     * Returns null if the property does not exist.
     *
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
     * Instantiates the attributes of the class, followed by those of its
     * parent classes and interfaces. Attributes that cannot be instantiated,
     * e.g. because their class is not loaded, are skipped.
     *
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
     * Instantiates the attributes of the property, as declared in the class
     * and in each of its parent classes that declares the same property.
     * Attributes that cannot be instantiated are skipped.
     *
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

            // `getProperty()` also returns non-private properties inherited
            // from a parent class. Those are handled when the parent class
            // itself is visited.
            if ($reflectionProperty->getDeclaringClass()->getName() !== $class) {
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
