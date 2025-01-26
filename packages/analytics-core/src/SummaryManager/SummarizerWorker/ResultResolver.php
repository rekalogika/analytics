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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\TimeZoneAwareDimensionHierarchy;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class ResultResolver
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly SummaryMetadata $metadata,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param list<array<string,mixed>> $result
     * @return iterable<array<string,array{mixed,mixed}>>
     */
    public function resolveResult(iterable $result): iterable
    {
        foreach ($result as $row) {
            $grouping = $row['__grouping'] ?? '';

            if (!\is_string($grouping)) {
                throw new \LogicException('Grouping column not found');
            }

            $groupingCount = substr_count($grouping, '1');

            if ($groupingCount !== 0) {
                continue;
            }

            yield $this->mapArrayToSummaryObjectAndBackAgain($row);
        }
    }

    /**
     * @param array<string,mixed> $inputArray
     * @return array<string,array{mixed,mixed}>
     */
    private function mapArrayToSummaryObjectAndBackAgain(
        array $inputArray,
    ): array {
        $summaryObject = $this->mapArrayToSummaryObject($inputArray);

        $outputArray = [];

        /** @var mixed $rawValue */
        foreach ($inputArray as $key => $rawValue) {
            if ($key === '__grouping') {
                continue;
            }

            if ($this->isMeasure($key)) {
                $rawValue = $this->castToNumber($rawValue);
            }

            $outputArray[$key] = [
                $rawValue,
                $this->propertyAccessor->getValue($summaryObject, $key),
            ];
        }

        return $outputArray;
    }

    private function isMeasure(string $key): bool
    {
        return $this->metadata->isMeasure($key);
    }

    /**
     *
     *
     * @param array<string,mixed> $array
     */
    private function mapArrayToSummaryObject(array $array): object
    {
        $summaryClassName = $this->metadata->getSummaryClass();
        $reflectionClass = new \ReflectionClass($summaryClassName);
        $summaryObject = $reflectionClass->newInstanceWithoutConstructor();

        /** @var mixed $value */
        foreach ($array as $key => $value) {
            if ($key === '__grouping') {
                continue;
            }

            /** @var mixed */
            $value = $this->resolveValue(
                reflectionClass: $reflectionClass,
                key: $key,
                value: $value,
            );

            $this->injectValueToObject(
                object: $summaryObject,
                reflectionClass: $reflectionClass,
                propertyName: $key,
                value: $value,
            );
        }

        return $summaryObject;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function resolveValue(
        \ReflectionClass $reflectionClass,
        string $key,
        mixed $value,
    ): mixed {
        if (str_contains($key, '.')) {
            return $value;
            // [$propertyName, $hierarchyPropertyName] = explode('.', $key);

            // $reflectionProperty = $reflectionClass->getProperty($propertyName);
            // $reflectionType = $reflectionProperty->getType();

            // if ($reflectionType instanceof \ReflectionNamedType) {
            //     $propertyClass = $reflectionType->getName();

            //     if (!class_exists($propertyClass)) {
            //         return $value;
            //     }
            // } else {
            //     return $value;
            // }
        } else {
            $reflectionProperty = $reflectionClass->getProperty($key);
            $propertyClass = $this->getTypeOfProperty($reflectionProperty);

            if ($propertyClass !== null) {
                if (is_a($propertyClass, \BackedEnum::class, true)) {
                    // for older Doctrine version that don't correctly hydrate
                    // enums with QueryBuilder
                    if ((\is_int($value) || \is_string($value))) {
                        $value = $propertyClass::from($value);
                    }
                } elseif ($value !== null) {
                    $value = $this->entityManager
                        ->getReference($propertyClass, $value);
                }
            }

            return $value;
        }
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function injectValueToObject(
        object $object,
        \ReflectionClass $reflectionClass,
        string $propertyName,
        mixed $value,
    ): void {
        if (str_contains($propertyName, '.')) {
            [$propertyName, $hierarchyPropertyName] = explode('.', $propertyName);

            $this->injectValueToHierarchyObject(
                object: $object,
                reflectionClass: $reflectionClass,
                propertyName: $propertyName,
                hierarchyPropertyName: $hierarchyPropertyName,
                value: $value,
            );

            return;
        }

        while (true) {
            if ($reflectionClass->hasProperty($propertyName)) {
                $property = $reflectionClass->getProperty($propertyName);
                $property->setAccessible(true);
                $property->setValue($object, $value);

                return;
            }

            $reflectionClass = $reflectionClass->getParentClass();

            if ($reflectionClass === false) {
                throw new \LogicException('Property not found');
            }
        }
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function injectValueToHierarchyObject(
        object $object,
        \ReflectionClass $reflectionClass,
        string $propertyName,
        string $hierarchyPropertyName,
        mixed $value,
    ): void {
        // initialize hierarchy object

        $curReflectionClass = $reflectionClass;
        $hierarchyObjectReflection = null;
        $hierarchyObject = null;

        while (true) {
            if ($curReflectionClass->hasProperty($propertyName)) {
                $reflectionProperty = $curReflectionClass->getProperty($propertyName);
                $reflectionProperty->setAccessible(true);

                if (!$reflectionProperty->isInitialized($object)) {
                    $reflectionType = $reflectionProperty->getType();

                    if (!$reflectionType instanceof \ReflectionNamedType) {
                        throw new \LogicException('Property type not found');
                    }

                    $propertyClass = $reflectionType->getName();

                    if (!class_exists($propertyClass)) {
                        throw new \LogicException('Property class not found');
                    }

                    $hierarchyClassReflection = new \ReflectionClass($propertyClass);
                    $hierarchyObject = $hierarchyClassReflection->newInstanceWithoutConstructor();

                    $reflectionProperty->setValue($object, $hierarchyObject);
                }

                /** @var mixed */
                $hierarchyObject = $reflectionProperty->getValue($object);

                if (!\is_object($hierarchyObject)) {
                    throw new \LogicException('Hierarchy object not found');
                }

                $hierarchyObjectReflection = new \ReflectionObject($hierarchyObject);

                break;
            }

            $curReflectionClass = $curReflectionClass->getParentClass();

            if ($curReflectionClass === false) {
                throw new \LogicException('Property not found');
            }
        }

        // inject time zone if applicable

        if ($hierarchyObject instanceof TimeZoneAwareDimensionHierarchy) {
            $timeZone = $this->metadata
                ->getDimensionMetadata($propertyName)
                ->getSummaryTimeZone();

            $hierarchyObject->setTimeZone($timeZone);
        }

        // inject value to hierarchy object

        if ($hierarchyObjectReflection === null) {
            throw new \LogicException('Hierarchy object not found');
        }

        while (true) {
            if ($hierarchyObjectReflection->hasProperty($hierarchyPropertyName)) {
                $property = $hierarchyObjectReflection->getProperty($hierarchyPropertyName);
                $property->setAccessible(true);
                $property->setValue($hierarchyObject, $value);

                return;
            }

            $hierarchyObjectReflection = $hierarchyObjectReflection->getParentClass();

            if ($hierarchyObjectReflection === false) {
                throw new \LogicException('Property not found');
            }
        }
    }

    /**
     * @return class-string|null
     */
    private function getTypeOfProperty(\ReflectionProperty $property): ?string
    {
        $type = $property->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        $class = $type->getName();

        if (!class_exists($class)) {
            return null;
        }

        return $class;
    }

    private function castToNumber(mixed $value): int|float|null
    {
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return null;
    }
}
