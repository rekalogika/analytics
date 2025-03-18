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
use Rekalogika\Analytics\NumericValueResolver;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimensions;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasures;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultRow;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTuple;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultUnit;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\TimeZoneAwareDimensionHierarchy;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class QueryResultToRowTransformer
{
    private function __construct(
        private readonly SummaryQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly EntityManagerInterface $entityManager,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {}

    /**
     * @param list<array<string,mixed>> $input
     */
    public static function transform(
        SummaryQuery $query,
        SummaryMetadata $metadata,
        EntityManagerInterface $entityManager,
        PropertyAccessorInterface $propertyAccessor,
        array $input,
    ): DefaultTable {
        $transformer = new self(
            query: $query,
            metadata: $metadata,
            entityManager: $entityManager,
            propertyAccessor: $propertyAccessor,
        );

        $rows = $transformer->doTransform($input);

        return new DefaultTable(
            summaryClass: $metadata->getSummaryClass(),
            rows: $rows,
        );
    }

    /**
     * @param list<array<string,mixed>> $input
     * @return list<DefaultRow>
     */
    private function doTransform(array $input): array
    {
        $rows = [];

        foreach ($input as $item) {
            $row = $this->transformOne($item);

            if ($row->isSubtotal()) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $input
     */
    public function transformOne(array $input): DefaultRow
    {
        // create the object
        $summaryClassName = $this->metadata->getSummaryClass();
        $reflectionClass = new \ReflectionClass($summaryClassName);
        $summaryObject = $reflectionClass->newInstanceWithoutConstructor();

        //
        // process measures
        //

        $measures = $this->query->getSelect();
        $measureValues = [];

        foreach ($measures as $key) {
            if (!\array_key_exists($key, $input)) {
                throw new \LogicException(\sprintf('Measure "%s" not found', $key));
            }

            /** @psalm-suppress MixedAssignment */
            $rawValue = $input[$key];

            /** @psalm-suppress MixedAssignment */
            $rawValue = $this->resolveValue(
                reflectionClass: $reflectionClass,
                propertyName: $key,
                value: $rawValue,
            );

            $this->injectValueToObject(
                object: $summaryObject,
                reflectionClass: $reflectionClass,
                propertyName: $key,
                value: $rawValue,
            );

            /** @psalm-suppress MixedAssignment */
            $value = $this->propertyAccessor->getValue($summaryObject, $key);
            $numericValueResolver = $this->getNumericValueResolver($key);

            $numericValue = $numericValueResolver->resolveNumericValue(
                value: $value,
                rawValue: $rawValue,
            );

            $unit = $this->metadata
                ->getMeasureMetadata($key)
                ->getUnit();

            $unitSignature = $this->metadata
                ->getMeasureMetadata($key)
                ->getUnitSignature();

            $unit = DefaultUnit::create(
                label: $unit,
                signature: $unitSignature,
            );

            $measure = new DefaultMeasure(
                label: $this->getLabel($key),
                key: $key,
                value: $value,
                rawValue: $rawValue,
                numericValue: $numericValue,
                unit: $unit,
            );

            $measureValues[$key] = $measure;
        }

        //
        // process grouping
        //

        /** @psalm-suppress MixedAssignment */
        $groupings = $input['__grouping'] ?? throw new \LogicException('Grouping not found');

        if (!\is_string($groupings)) {
            throw new \LogicException('Grouping is not a string');
        }

        //
        // process dimensions
        //

        $dimensionValues = [];
        $dimensions = $this->query->getGroupBy();

        foreach ($dimensions as $key) {
            if ($key === '@values') {
                continue;
            }

            if (!\array_key_exists($key, $input)) {
                throw new \LogicException(\sprintf('Dimension "%s" not found', $key));
            }

            /** @psalm-suppress MixedAssignment */
            $rawValue = $input[$key];

            /** @psalm-suppress MixedAssignment */
            $rawValue = $this->resolveValue(
                reflectionClass: $reflectionClass,
                propertyName: $key,
                value: $rawValue,
            );

            $this->injectValueToObject(
                object: $summaryObject,
                reflectionClass: $reflectionClass,
                propertyName: $key,
                value: $rawValue,
            );

            /** @psalm-suppress MixedAssignment */
            $value = $this->propertyAccessor->getValue($summaryObject, $key);

            /** @psalm-suppress MixedAssignment */
            $displayValue = $value ?? $this->getNullValue($key);

            $dimension = new DefaultDimension(
                label: $this->getLabel($key),
                key: $key,
                member: $value,
                rawMember: $rawValue,
                displayMember: $displayValue,
            );

            $dimensionValues[$key] = $dimension;
        }

        $dimensions = new DefaultDimensions($dimensionValues);
        $tuple = new DefaultTuple($dimensions);
        $measures = new DefaultMeasures($measureValues);

        return new DefaultRow(
            tuple: $tuple,
            measures: $measures,
            groupings: $groupings,
        );
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function resolveValue(
        \ReflectionClass $reflectionClass,
        string $propertyName,
        mixed $value,
    ): mixed {
        if (str_contains($propertyName, '.')) {
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
            $reflectionProperty = $this->getReflectionProperty($reflectionClass, $propertyName);
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
    private function getReflectionProperty(
        \ReflectionClass $reflectionClass,
        string $propertyName,
    ): \ReflectionProperty {
        if ($reflectionClass->hasProperty($propertyName)) {
            return $reflectionClass->getProperty($propertyName);
        }

        $parent = $reflectionClass->getParentClass();

        if ($parent === false) {
            throw new \LogicException(\sprintf('Property "%s" not found in class "%s"', $propertyName, $reflectionClass->getName()));
        }

        return $this->getReflectionProperty($parent, $propertyName);
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

    private function getLabel(string $property): TranslatableInterface
    {
        return $this->metadata
            ->getFullyQualifiedProperty($property)
            ->getLabel();
    }

    private function getNullValue(string $dimension): TranslatableInterface
    {
        return $this->metadata
            ->getFullyQualifiedDimension($dimension)
            ->getNullLabel();
    }

    private function getNumericValueResolver(string $measure): NumericValueResolver
    {
        return $this->metadata
            ->getMeasureMetadata($measure)
            ->getNumericValueResolver();
    }
}
