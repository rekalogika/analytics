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

namespace Rekalogika\Analytics\SummaryManager;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Summary\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Metadata\DimensionMetadata;
use Rekalogika\Analytics\Metadata\MeasureMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @template T of object
 * @implements SummaryManager<T>
 */
final readonly class DefaultSummaryManager implements SummaryManager
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        private string $class,
        private EntityManagerInterface $entityManager,
        private SummaryMetadata $metadata,
        private PropertyAccessorInterface $propertyAccessor,
        private SummaryRefresherFactory $refresherFactory,
        private DistinctValuesResolver $distinctValuesResolver,
        private int $queryResultLimit,
    ) {}

    #[\Override]
    public function updateBySourceRange(
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void {
        $this->refresherFactory
            ->createSummaryRefresher($this->class)
            ->manualRefresh(
                start: $start,
                end: $end,
                batchSize: $batchSize,
                resumeId: $resumeId,
            );
    }

    /**
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getDimensionMetadatas(): array
    {
        return $this->metadata->getDimensionMetadatas();
    }

    /**
     * @return array<string,MeasureMetadata>
     */
    public function getMeasureMetadatas(): array
    {
        return $this->metadata->getMeasureMetadatas();
    }

    /**
     * @internal
     * @return non-empty-array<string,string|TranslatableInterface|\Stringable|iterable<string,TranslatableInterface>>
     */
    private function getHierarchicalDimensionChoices(): array
    {
        $choices = [];

        foreach ($this->getDimensionMetadatas() as $dimensionMetadata) {
            $hierarchy = $dimensionMetadata->getHierarchy();

            // if not hierarchical

            if ($hierarchy === null) {
                $choices[$dimensionMetadata->getSummaryProperty()] = $dimensionMetadata->getLabel();

                continue;
            }

            // if hierarchical

            $children = [];

            foreach ($hierarchy->getProperties() as $property) {
                $children[$property->getName()] = $property->getLabel();
            }

            $choices[$dimensionMetadata->getSummaryProperty()] =
                new HierarchicalDimension(
                    label: $dimensionMetadata->getLabel(),
                    children: $children,
                );
        }

        /** @var non-empty-array<string,string|TranslatableInterface|\Stringable|iterable<string,TranslatableInterface>> */

        return $choices;
    }

    /**
     * @return array<string,Field>
     */
    private function getDimensionChoices(): array
    {
        $choices = [];

        foreach ($this->getDimensionMetadatas() as $dimensionMetadata) {
            $hierarchy = $dimensionMetadata->getHierarchy();

            // if not hierarchical

            if ($hierarchy === null) {
                $field = new Field(
                    key: $dimensionMetadata->getSummaryProperty(),
                    label: $dimensionMetadata->getLabel(),
                    subLabel: null,
                );

                $choices[$field->getKey()] = $field;

                continue;
            }

            // if hierarchical

            foreach ($hierarchy->getProperties() as $property) {
                $fullProperty = \sprintf(
                    '%s.%s',
                    $dimensionMetadata->getSummaryProperty(),
                    $property->getName(),
                );

                $field = new Field(
                    key: $fullProperty,
                    label: $dimensionMetadata->getLabel(),
                    subLabel: $property->getLabel(),
                );

                $choices[$field->getKey()] = $field;
            }
        }

        return $choices;
    }

    /**
     * @return array<string,Field>
     */
    private function getMeasureChoices(): array
    {
        $choices = [];

        foreach ($this->getMeasureMetadatas() as $measureMetadata) {
            $field = new Field(
                key: $measureMetadata->getSummaryProperty(),
                label: $measureMetadata->getLabel(),
                subLabel: null,
            );

            $choices[$field->getKey()] = $field;
        }

        return $choices;
    }


    #[\Override]
    public function createQuery(
        ?int $queryResultLimit = null,
    ): SummaryQuery {
        $dimensionChoices = [
            ...$this->getDimensionChoices(),
            '@values' => new Field(
                key: '@values',
                label: new TranslatableMessage('Values'),
                subLabel: null,
            ),
        ];

        return new SummaryQuery(
            dimensionChoices: $dimensionChoices,
            hierarchicalDimensionChoices: $this->getHierarchicalDimensionChoices(),
            measureChoices: $this->getMeasureChoices(),
            entityManager: $this->entityManager,
            metadata: $this->metadata,
            propertyAccessor: $this->propertyAccessor,
            distinctValuesResolver: $this->distinctValuesResolver,
            queryResultLimit: $queryResultLimit ?? $this->queryResultLimit,
        );
    }
}
