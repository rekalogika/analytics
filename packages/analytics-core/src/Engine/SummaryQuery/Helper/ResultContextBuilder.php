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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Context\PseudoMeasureContext;
use Rekalogika\Analytics\Contracts\Context\SummaryContext;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Summary\ContextAwareSummary;
use Rekalogika\Analytics\Contracts\Summary\PseudoMeasure;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCoordinates;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultDimension;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultMeasureMember;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultMeasures;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultUnit;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

final class ResultContextBuilder
{
    private readonly QueryResultToTableHelper $helper;

    /**
     * @var list<string>
     */
    private readonly array $dimensions;

    /**
     * @var array<string,DefaultMeasureMember>
     */
    private array $measureMemberCache = [];

    private readonly QueryProcessor $queryProcessor;

    private ?ResultContext $resultContext = null;

    public function __construct(
        private readonly DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly EntityManagerInterface $entityManager,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly int $nodesLimit,
        int $queryResultLimit,
        private readonly SourceEntitiesFactory $sourceEntitiesFactory,
        private readonly ResultContextFactory $resultContextFactory,
        private readonly TranslatableInterface $measureLabel = new TranslatableMessage('Values'),
    ) {
        $this->queryProcessor = new QueryProcessor(
            query: $query,
            metadata: $metadata,
            entityManager: $entityManager,
            queryResultLimit: $queryResultLimit,
        );

        $this->helper = new QueryResultToTableHelper();

        $this->dimensions = array_values(array_filter(
            $this->query->getDimensions(),
            static fn(string $dimension): bool => $dimension !== '@values',
        ));
    }

    public function getResultContext(): ResultContext
    {
        if ($this->resultContext !== null) {
            return $this->resultContext;
        }

        $input = $this->queryProcessor->getQueryResult();

        $resultContext = new ResultContext(
            metadata: $this->metadata,
            query: $this->query,
            nodesLimit: $this->nodesLimit,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
            resultContextFactory: $this->resultContextFactory,
        );

        $cellRepository = $resultContext->getCellRepository();

        foreach ($input as $item) {
            $cell = $this->transformOne(
                resultContext: $resultContext,
                input: $item,
            );

            $cellRepository->collectCell($cell);

            foreach ($this->unpivotRow($resultContext, $cell) as $row) {
                $cellRepository->collectCell($row);
            }
        }

        return $this->resultContext = $resultContext;
    }

    /**
     * @param array<string,mixed> $input
     */
    private function transformOne(
        ResultContext $resultContext,
        array $input,
    ): DefaultCell {
        // create the object
        $summaryClass = $this->metadata->getSummaryClass();
        $reflectionClass = new \ReflectionClass($summaryClass);
        $summaryObject = $reflectionClass->newInstanceWithoutConstructor();

        // create groupings
        $groupings = new GroupingField(
            groupingField: $input['__grouping'] ?? null,
            dimensions: $this->dimensions,
        );

        // contextawareness init
        if ($summaryObject instanceof ContextAwareSummary) {
            $summaryObject->setContext(
                context: new SummaryContext(
                    summaryMetadata: $this->metadata,
                    rawInput: $input,
                ),
            );
        }

        // populate dimensions
        $this->populateDimensions(
            input: $input,
            summaryObject: $summaryObject,
        );

        // get dimensions from object
        $dimensionValues = $this->createDimensionValues(
            resultContext: $resultContext,
            summaryObject: $summaryObject,
            groupingField: $groupings,
        );

        // create coordinates
        $coordinates = new DefaultCoordinates(
            summaryClass: $this->metadata->getSummaryClass(),
            dimensions: array_values($dimensionValues),
            condition: $this->query->getDice(),
        );

        // populate measures
        $this->populateMeasures(
            input: $input,
            summaryObject: $summaryObject,
            coordinates: $coordinates,
        );

        // get measures from object
        $measureValues = $this->createMeasureValues($resultContext, $summaryObject);
        $measures = new DefaultMeasures($measureValues);

        return new DefaultCell(
            coordinates: $coordinates,
            measures: $measures,
            isNull: false,
            context: $resultContext,
            sourceEntitiesFactory: $this->sourceEntitiesFactory,
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    private function populateDimensions(
        array $input,
        object $summaryObject,
    ): void {
        foreach ($this->dimensions as $name) {
            if (!\array_key_exists($name, $input)) {
                throw new LogicException(\sprintf('Dimension "%s" not found', $name));
            }

            /** @psalm-suppress MixedAssignment */
            $rawValue = $this->normalizeRawValue(
                propertyName: $name,
                value: $input[$name],
            );

            $this->helper->setValue(
                object: $summaryObject,
                property: $name,
                value: $rawValue,
                metadata: $this->metadata,
            );
        }
    }

    /**
     * @param array<string,mixed> $input
     */
    private function populateMeasures(
        array $input,
        object $summaryObject,
        DefaultCoordinates $coordinates,
    ): void {
        foreach ($this->metadata->getMeasures() as $name => $measureMetadata) {
            $function = $measureMetadata->getFunction();

            if ($function instanceof PseudoMeasure) {
                /** @psalm-suppress MixedAssignment */
                $rawValue = $this->createPseudoMeasureValue(
                    measureMetadata: $measureMetadata,
                    coordinates: $coordinates,
                );
            } else {
                /** @psalm-suppress MixedAssignment */
                $rawValue = $this->normalizeRawValue(
                    propertyName: $name,
                    value: $input[$name],
                );
            }

            $this->helper->setValue(
                object: $summaryObject,
                property: $name,
                value: $rawValue,
                metadata: $this->metadata,
            );
        }
    }

    /**
     * @return array<string,DefaultDimension>
     */
    private function createDimensionValues(
        ResultContext $resultContext,
        object $summaryObject,
        GroupingField $groupingField,
    ): array {
        $dimensions = $groupingField->getNonGroupingFields();
        $dimensionValues = [];

        foreach ($dimensions as $name) {
            /** @psalm-suppress MixedAssignment */
            $rawValue = $this->helper->getValue(
                object: $summaryObject,
                property: $name,
                metadata: $this->metadata,
            );

            /** @psalm-suppress MixedAssignment */
            $value = $this->propertyAccessor->getValue($summaryObject, $name);

            /** @psalm-suppress MixedAssignment */
            $displayValue = $value ?? $this->getNullValue($name);

            $dimension = $resultContext
                ->getDimensionFactory()
                ->createDimension(
                    label: $this->getLabel($name),
                    name: $name,
                    member: $value,
                    rawMember: $rawValue,
                    displayMember: $displayValue,
                    interpolation: false,
                );

            if ($groupingField->hasOneNonGroupingField()) {
                $resultContext
                    ->getDimensionCollection()
                    ->collectDimension($dimension);
            }

            $dimensionValues[$name] = $dimension;
        }

        return $dimensionValues;
    }

    /**
     * @return array<string,DefaultMeasure>
     */
    private function createMeasureValues(
        ResultContext $resultContext,
        object $summaryObject,
    ): array {
        $measureValues = [];

        foreach ($this->metadata->getMeasures() as $name => $measureMetadata) {
            /** @psalm-suppress MixedAssignment */
            $rawValue = $this->helper->getValue(
                object: $summaryObject,
                property: $name,
                metadata: $this->metadata,
            );

            /** @psalm-suppress MixedAssignment */
            $value = $this->propertyAccessor->getValue($summaryObject, $name);

            $unit = $this->metadata
                ->getMeasure($name)
                ->getUnit();

            $unitSignature = $this->metadata
                ->getMeasure($name)
                ->getUnitSignature();

            $unit = DefaultUnit::create(
                label: $unit,
                signature: $unitSignature,
            );

            $measure = new DefaultMeasure(
                label: $this->getLabel($name),
                name: $name,
                value: $value,
                rawValue: $rawValue,
                unit: $unit,
            );

            $measureValues[$name] = $measure;

            $resultContext->getNullMeasureCollection()->collectMeasure($measure);
        }

        return $measureValues;
    }

    private function createPseudoMeasureValue(
        MeasureMetadata $measureMetadata,
        DefaultCoordinates $coordinates,
    ): mixed {
        $context = new PseudoMeasureContext(
            measureMetadata: $measureMetadata,
            coordinates: $coordinates,
        );

        $function = $measureMetadata->getFunction();

        if (!$function instanceof PseudoMeasure) {
            throw new LogicException('Function must be an instance of PseudoMeasure');
        }

        return $function->createPseudoMeasure($context);
    }

    private function normalizeRawValue(
        string $propertyName,
        mixed $value,
    ): mixed {
        $propertyClass = $this->metadata
            ->getProperty($propertyName)
            ->getTypeClass();

        if ($value === null || \is_object($value)) {
            return $value;
        }

        if ($propertyClass === null) {
            return $value;
        }

        // for older Doctrine version that don't correctly hydrate
        // enums with QueryBuilder
        if (is_a($propertyClass, \BackedEnum::class, true) && (\is_int($value) || \is_string($value))) {
            return $propertyClass::from($value);
        }

        // determine if propertyClass is an entity
        $isEntity = !$this->entityManager
            ->getMetadataFactory()
            ->isTransient($propertyClass);

        if ($isEntity) {
            return $this->entityManager
                ->getReference($propertyClass, $value);
        }

        return $value;
    }

    private function getLabel(string $property): TranslatableInterface
    {
        $property = $this->metadata->getProperty($property);

        if ($property instanceof DimensionMetadata) {
            return $property->getLabel()->getRootToLeaf();
        }

        return $property->getLabel();
    }

    private function getNullValue(string $dimension): TranslatableInterface
    {
        return $this->metadata
            ->getDimension($dimension)
            ->getNullLabel();
    }

    /**
     * @return iterable<DefaultCell>
     */
    private function unpivotRow(
        ResultContext $resultContext,
        DefaultCell $cell,
    ): iterable {
        $measures = array_keys($this->metadata->getMeasures());

        foreach ($measures as $measure) {
            $measureMember = $this->getMeasureMember($measure);

            $measureDimension = $resultContext
                ->getDimensionFactory()
                ->createDimension(
                    label: $this->measureLabel,
                    name: '@values',
                    member: $measureMember,
                    rawMember: $measureMember,
                    displayMember: $measureMember,
                    interpolation: false,
                );

            $resultContext->getDimensionCollection()
                ->collectDimension($measureDimension);

            $measure = $cell->getMeasures()->get($measure)
                ?? throw new UnexpectedValueException(
                    \sprintf('Measure "%s" not found in row', $measure),
                );

            $coordinates = $cell->getCoordinates()->append($measureDimension);

            $measures = new DefaultMeasures([
                $measure->getName() => $measure,
            ]);

            yield new DefaultCell(
                coordinates: $coordinates,
                measures: $measures,
                isNull: false,
                context: $resultContext,
                sourceEntitiesFactory: $this->sourceEntitiesFactory,
            );
        }
    }

    private function getMeasureMember(string $measure): DefaultMeasureMember
    {
        return $this->measureMemberCache[$measure] ??= new DefaultMeasureMember(
            label: $this->metadata->getMeasure($measure)->getLabel(),
            property: $measure,
        );
    }
}
