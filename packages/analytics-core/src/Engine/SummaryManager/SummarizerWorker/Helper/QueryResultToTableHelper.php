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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Context\DimensionGroupContext;
use Rekalogika\Analytics\Contracts\DimensionGroup\ContextAwareDimensionGroup;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\PropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class QueryResultToTableHelper
{
    public function getValue(
        object $object,
        string $property,
        SummaryMetadata|PropertyMetadata $metadata,
    ): mixed {
        $propertyParts = explode('.', $property);

        if (\count($propertyParts) < 1) {
            throw new LogicException('Property name cannot be empty');
        }

        return $this->get(
            object: $object,
            propertyParts: $propertyParts,
            metadata: $metadata,
        );
    }

    public function setValue(
        object $object,
        string $property,
        mixed $value,
        SummaryMetadata|PropertyMetadata $metadata,
    ): void {
        $propertyParts = explode('.', $property);

        if (\count($propertyParts) < 1) {
            throw new LogicException('Property name cannot be empty');
        }

        $this->set(
            object: $object,
            propertyParts: $propertyParts,
            value: $value,
            metadata: $metadata,
        );
    }

    private function getChildMetadata(
        SummaryMetadata|PropertyMetadata $metadata,
        string $property,
    ): DimensionMetadata {
        if (
            !$metadata instanceof SummaryMetadata
            && !$metadata instanceof DimensionMetadata
        ) {
            throw new LogicException(\sprintf(
                'Metadata of type "%s" cannot have children',
                $metadata::class,
            ));
        }

        $metadata = $metadata->getChild($property);

        if (!$metadata instanceof DimensionMetadata) {
            throw new LogicException(\sprintf(
                'Property "%s" is not a valid metadata type',
                $property,
            ));
        }

        return $metadata;
    }

    /**
     * @param non-empty-list<string> $propertyParts
     */
    private function get(
        object $object,
        array $propertyParts,
        SummaryMetadata|PropertyMetadata $metadata,
    ): mixed {
        if (\count($propertyParts) > 1) {
            $property = array_shift($propertyParts);

            /** @psalm-suppress MixedAssignment */
            $object = $this->get(
                object: $object,
                propertyParts: [$property],
                metadata: $metadata,
            );

            if (!\is_object($object)) {
                return null;
            }

            $metadata = $this->getChildMetadata($metadata, $property);

            /** @var non-empty-list<string> $propertyParts */

            return $this->get(
                object: $object,
                propertyParts: $propertyParts,
                metadata: $metadata,
            );
        }

        $property = $propertyParts[0];
        $reflectionClass = new \ReflectionClass($object);

        while (true) {
            if ($reflectionClass->hasProperty($property)) {
                break;
            }

            $reflectionClass = $reflectionClass->getParentClass();

            if ($reflectionClass === false) {
                throw new LogicException(\sprintf(
                    'Property "%s" not found in class "%s"',
                    $property,
                    $object::class,
                ));
            }
        }

        $propertyReflection = $reflectionClass->getProperty($property);

        if ($propertyReflection->isStatic()) {
            throw new LogicException(\sprintf(
                'Property "%s" in class "%s" is static, cannot set value',
                $property,
                $object::class,
            ));
        }

        if (!$propertyReflection->isInitialized($object)) {
            throw new LogicException(\sprintf(
                'Property "%s" in class "%s" is not initialized',
                $property,
                $object::class,
            ));
        }

        return $propertyReflection->getValue($object);
    }

    /**
     * @param non-empty-list<string> $propertyParts
     */
    private function set(
        object $object,
        array $propertyParts,
        mixed $value,
        SummaryMetadata|PropertyMetadata $metadata,
    ): void {
        if (\count($propertyParts) > 1) {
            $property = array_shift($propertyParts);

            $object = $this->maybeInitialize(
                object: $object,
                property: $property,
                metadata: $metadata,
            );

            $metadata = $this->getChildMetadata($metadata, $property);

            /** @var non-empty-list<string> $propertyParts */

            $this->set(
                object: $object,
                propertyParts: $propertyParts,
                value: $value,
                metadata: $metadata,
            );

            return;
        }

        $property = $propertyParts[0];
        $reflectionClass = new \ReflectionClass($object);

        while (true) {
            if ($reflectionClass->hasProperty($property)) {
                break;
            }

            $reflectionClass = $reflectionClass->getParentClass();

            if ($reflectionClass === false) {
                throw new LogicException(\sprintf(
                    'Property "%s" not found in class "%s"',
                    $property,
                    $object::class,
                ));
            }
        }

        $propertyReflection = $reflectionClass->getProperty($property);

        if ($propertyReflection->isStatic()) {
            throw new LogicException(\sprintf(
                'Property "%s" in class "%s" is static, cannot set value',
                $property,
                $object::class,
            ));
        }

        $propertyReflection->setValue($object, $value);
    }

    private function maybeInitialize(
        object $object,
        string $property,
        SummaryMetadata|PropertyMetadata $metadata,
    ): object {
        if (
            !$metadata instanceof SummaryMetadata
            && !$metadata instanceof DimensionMetadata
        ) {
            throw new LogicException(\sprintf(
                'Metadata of type "%s" cannot have children',
                $metadata::class,
            ));
        }

        $metadata = $metadata->getChild($property);

        $reflectionClass = new \ReflectionClass($object);

        while (true) {
            if ($reflectionClass->hasProperty($property)) {
                break;
            }

            $reflectionClass = $reflectionClass->getParentClass();

            if ($reflectionClass === false) {
                throw new LogicException(\sprintf(
                    'Property "%s" not found in class "%s"',
                    $property,
                    \get_class($object),
                ));
            }
        }

        $propertyReflection = $reflectionClass->getProperty($property);

        if ($propertyReflection->isStatic()) {
            throw new LogicException(\sprintf(
                'Property "%s" in class "%s" is static, cannot initialize',
                $property,
                \get_class($object),
            ));
        }

        if ($propertyReflection->isInitialized($object)) {
            /** @psalm-suppress MixedAssignment */
            $existingValue = $propertyReflection->getValue($object);

            if (\is_object($existingValue)) {
                return $existingValue;
            }
        }

        $type = $propertyReflection->getType();

        if ($type === null) {
            throw new LogicException(\sprintf(
                'Property "%s" in class "%s" has no type',
                $property,
                \get_class($object),
            ));
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new LogicException(\sprintf(
                'Property "%s" in class "%s" has a non-named type',
                $property,
                \get_class($object),
            ));
        }

        if ($type->isBuiltin()) {
            throw new LogicException(\sprintf(
                'Property "%s" in class "%s" is a built-in type, cannot initialize',
                $property,
                \get_class($object),
            ));
        }

        $class = $type->getName();

        if (!class_exists($class)) {
            throw new LogicException(\sprintf(
                'Class "%s" for property "%s" in class "%s" does not exist',
                $class,
                $property,
                \get_class($object),
            ));
        }

        $newClassReflection = new \ReflectionClass($class);
        $newObject = $newClassReflection->newInstanceWithoutConstructor();

        if ($newObject instanceof ContextAwareDimensionGroup) {
            // if (!$metadata instanceof DimensionMetadata) {
            //     throw new LogicException(\sprintf(
            //         'Metadata for property "%s" is not a dimension metadata',
            //         $property,
            //     ));
            // }

            // $propertyMetadata = $metadata->getChild($property);

            if (!$metadata instanceof DimensionMetadata) {
                throw new LogicException(\sprintf(
                    'Property "%s" is not a dimension property',
                    $property,
                ));
            }

            $this->initializeContextAwareDimensionGroup(
                object: $newObject,
                metadata: $metadata,
            );
        }

        $propertyReflection->setValue($object, $newObject);

        return $newObject;
    }

    private function initializeContextAwareDimensionGroup(
        ContextAwareDimensionGroup $object,
        PropertyMetadata $metadata,
    ): void {
        if ($metadata instanceof DimensionMetadata) {
            $context = new DimensionGroupContext(
                dimensionMetadata: $metadata,
            );
        } else {
            throw new LogicException(\sprintf(
                'Property "%s" is not a dimension property',
                $metadata->getName(),
            ));
        }

        $object->setContext($context);
    }
}
