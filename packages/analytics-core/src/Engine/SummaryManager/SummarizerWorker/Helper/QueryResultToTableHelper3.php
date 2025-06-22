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
use Rekalogika\Analytics\Contracts\Context\HierarchyContext;
use Rekalogika\Analytics\Contracts\Hierarchy\ContextAwareHierarchy;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class QueryResultToTableHelper3
{
    public function __construct(
        private SummaryMetadata $metadata,
    ) {}

    public function writeValueDirectly(
        object $object,
        string $property,
        mixed $value,
    ): void {
        if (str_contains($property, '.')) {
            [$property, $hierarchyPropertyName] = explode('.', $property);

            try {
                /** @psalm-suppress MixedAssignment */
                $hierarchyObject = $this->readValueDirectly(
                    object: $object,
                    propertyName: $property,
                );
            } catch (\Error) {
                $hierarchyObject = $this->initializeHierarchyObject(
                    object: $object,
                    propertyName: $property,
                );
            }

            if (!\is_object($hierarchyObject)) {
                throw new LogicException(\sprintf(
                    'Property "%s" is not an object',
                    $property,
                ));
            }

            $this->writeValueDirectly(
                object: $hierarchyObject,
                property: $hierarchyPropertyName,
                value: $value,
            );

            return;
        }

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

        $reflectionClass
            ->getProperty($property)
            ->setValue($object, $value);
    }

    public function readValueDirectly(
        object $object,
        string $propertyName,
    ): mixed {
        if (str_contains($propertyName, '.')) {
            [$propertyName, $hierarchyPropertyName] = explode('.', $propertyName);

            /** @psalm-suppress MixedAssignment */
            $object = $this->readValueDirectly(
                object: $object,
                propertyName: $propertyName,
            );

            if (!\is_object($object)) {
                throw new LogicException(\sprintf(
                    'Property "%s" is not an object',
                    $propertyName,
                ));
            }

            return $this->readValueDirectly(
                object: $object,
                propertyName: $hierarchyPropertyName,
            );
        }

        $reflectionClass = new \ReflectionClass($object);

        while (true) {
            if ($reflectionClass->hasProperty($propertyName)) {
                break;
            }

            $reflectionClass = $reflectionClass->getParentClass();

            if ($reflectionClass === false) {
                throw new LogicException(\sprintf(
                    'Property "%s" not found in class "%s"',
                    $propertyName,
                    \get_class($object),
                ));
            }
        }

        return $reflectionClass
            ->getProperty($propertyName)
            ->getValue($object);
    }

    private function initializeHierarchyObject(
        object $object,
        string $propertyName,
    ): object {
        $propertyMetadata = $this->metadata->getProperty($propertyName);
        $className = $propertyMetadata->getTypeClass();

        if ($className === null) {
            throw new LogicException(\sprintf(
                'Property "%s" does not have a type class defined',
                $propertyName,
            ));
        }

        $reflectionClass = new \ReflectionClass($className);
        $hierarchyObject = $reflectionClass->newInstanceWithoutConstructor();

        if ($hierarchyObject instanceof ContextAwareHierarchy) {
            $this->initializeContextAwareHierarchyObject(
                object: $hierarchyObject,
                propertyName: $propertyName,
            );
        }

        $this->writeValueDirectly(
            object: $object,
            property: $propertyName,
            value: $hierarchyObject,
        );

        return $hierarchyObject;
    }

    private function initializeContextAwareHierarchyObject(
        ContextAwareHierarchy $object,
        string $propertyName,
    ): void {
        $propertyMetadata = $this->metadata->getProperty($propertyName);

        if ($propertyMetadata instanceof DimensionMetadata) {
            $context = new HierarchyContext(
                dimensionMetadata: $propertyMetadata,
            );
        } else {
            throw new LogicException(\sprintf(
                'Property "%s" is not a dimension property',
                $propertyName,
            ));
        }

        $object->setContext($context);
    }
}
