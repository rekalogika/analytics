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
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Metadata\DimensionMetadata;
use Rekalogika\Analytics\Metadata\MeasureMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\SummaryManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @template T of object
 * @implements SummaryManager<T>
 */
final readonly class DefaultSummaryManager implements SummaryManager
{
    private SummaryRefresher $refresher;

    private SummaryMetadata $metadata;

    /**
     * @param class-string<T> $class
     */
    public function __construct(
        string $class,
        private EntityManagerInterface $entityManager,
        SummaryMetadataFactory $metadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->metadata = $metadataFactory->getSummaryMetadata($class);

        $this->refresher = new SummaryRefresher(
            entityManager: $entityManager,
            metadata: $this->metadata,
            eventDispatcher: $this->eventDispatcher,
        );
    }

    #[\Override]
    public function updateBySourceRange(
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void {
        $this->refresher->refresh(
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
     * @return array<string,Item>
     */
    private function getDimensionChoices(): array
    {
        $choices = [];

        foreach ($this->getDimensionMetadatas() as $dimensionMetadata) {
            $hierarchy = $dimensionMetadata->getHierarchy();

            // if not hierarchical

            if ($hierarchy === null) {
                $item = new Item(
                    key: $dimensionMetadata->getSummaryProperty(),
                    label: $dimensionMetadata->getLabel(),
                    subLabel: null,
                );

                $choices[$item->getKey()] = $item;

                continue;
            }

            // if hierarchical

            foreach ($hierarchy->getProperties() as $property) {
                $fullProperty = \sprintf(
                    '%s.%s',
                    $dimensionMetadata->getSummaryProperty(),
                    $property->getName(),
                );

                $item = new Item(
                    key: $fullProperty,
                    label: $dimensionMetadata->getLabel(),
                    subLabel: $property->getLabel(),
                );

                $choices[$item->getKey()] = $item;
            }
        }

        return $choices;
    }

    /**
     * @return array<string,Item>
     */
    private function getMeasureChoices(): array
    {
        $choices = [];

        foreach ($this->getMeasureMetadatas() as $measureMetadata) {
            $item = new Item(
                key: $measureMetadata->getSummaryProperty(),
                label: $measureMetadata->getLabel(),
                subLabel: null,
            );

            $choices[$item->getKey()] = $item;
        }

        return $choices;
    }


    #[\Override]
    public function createQuery(): SummaryQuery
    {
        $dimensionChoices = [
            ...$this->getDimensionChoices(),
            '@values' => new Item(
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
        );
    }
}
