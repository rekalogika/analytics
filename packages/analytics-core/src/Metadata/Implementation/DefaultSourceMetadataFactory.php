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

namespace Rekalogika\Analytics\Metadata\Implementation;

use Rekalogika\Analytics\Metadata\Source\SourceMetadata;
use Rekalogika\Analytics\Metadata\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

final readonly class DefaultSourceMetadataFactory implements SourceMetadataFactory
{
    /**
     * @var array<class-string,array<string,list<class-string>>>
     */
    private array $involvedProperties;

    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {
        $this->involvedProperties = $this->createInvolvedProperties();
    }

    /**
     * @return iterable<string,SummaryMetadata>
     */
    private function getAllSummaryMetadata(): iterable
    {
        foreach ($this->summaryMetadataFactory->getSummaryClasses() as $summaryClass) {
            yield $summaryClass => $this->summaryMetadataFactory
                ->getSummaryMetadata($summaryClass);
        }
    }

    /**
     * Source class to the mapping of its properties to summary classes that
     * are affected by the change of the source property.
     *
     * @return array<class-string,array<string,list<class-string>>>
     */
    private function createInvolvedProperties(): array
    {
        $involvedProperties = [];

        foreach ($this->getAllSummaryMetadata() as $summaryMetadata) {
            $summaryClass = $summaryMetadata->getSummaryClass();
            $sourceClass = $summaryMetadata->getSourceClass();
            $properties = $summaryMetadata->getInvolvedProperties();

            foreach ($properties as $property) {
                $involvedProperties[$sourceClass][$property][] = $summaryClass;
            }
        }

        $uniqueInvolvedProperties = [];

        foreach ($involvedProperties as $sourceClass => $sourceProperties) {
            $uniqueInvolvedProperties[$sourceClass] = [];

            foreach ($sourceProperties as $sourceProperty => $summaryClasses) {
                $uniqueInvolvedProperties[$sourceClass][$sourceProperty] = array_values(array_unique($summaryClasses));
            }
        }

        return $uniqueInvolvedProperties;
    }

    #[\Override]
    public function getSourceMetadata(string $sourceClassName): SourceMetadata
    {
        $allPropertiesToSummaryClasses = [];

        $parents = class_parents($sourceClassName);

        if ($parents === false) {
            $parents = [];
        }

        $classes = [$sourceClassName, ...$parents];

        foreach ($classes as $class) {
            foreach ($this->involvedProperties[$class] ?? [] as $property => $summaryClasses) {
                foreach ($summaryClasses as $summaryClass) {
                    $allPropertiesToSummaryClasses[$property][] = $summaryClass;
                }
            }
        }

        return new SourceMetadata(
            class: $sourceClassName,
            propertyToSummaryClasses: $allPropertiesToSummaryClasses,
        );
    }
}
