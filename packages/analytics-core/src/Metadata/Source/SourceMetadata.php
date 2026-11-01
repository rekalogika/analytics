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

namespace Rekalogika\Analytics\Metadata\Source;

/**
 * Metadata of a source entity class: which summary classes depend on it, and
 * through which of its properties. The refresh engine uses it to decide which
 * summaries to mark as outdated when an entity is created, updated, or
 * deleted.
 */
final readonly class SourceMetadata
{
    /**
     * @var list<class-string>
     */
    private array $allInvolvedSummaryClasses;

    /**
     * @param class-string $class The source entity class.
     * @param array<string,list<class-string>> $propertyToSummaryClasses For
     * each property of the source, the summary classes whose values depend on
     * it.
     */
    public function __construct(
        private string $class,
        private array $propertyToSummaryClasses,
    ) {
        $allInvolvedSummaryClasses = [];

        foreach ($propertyToSummaryClasses as $summaryClasses) {
            $allInvolvedSummaryClasses = [
                ...$allInvolvedSummaryClasses,
                ...$summaryClasses,
            ];
        }

        $this->allInvolvedSummaryClasses = array_values(array_unique($allInvolvedSummaryClasses));
    }

    /**
     * The source entity class.
     *
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * For each property of the source, the summary classes whose values
     * depend on it.
     *
     * @return array<string,list<class-string>>
     */
    public function getPropertyToSummaryClasses(): array
    {
        return $this->propertyToSummaryClasses;
    }

    /**
     * Returns the summary classes affected by a change to the given
     * properties. Used when an entity is updated, so that summaries that do
     * not use any of the changed properties are not refreshed needlessly.
     *
     * @param list<string> $changedProperties
     * @return list<class-string>
     */
    public function getInvolvedSummaryClassesByChangedProperties(
        array $changedProperties,
    ): array {
        $involvedSummaryClasses = [];

        foreach ($changedProperties as $changedProperty) {
            if (isset($this->propertyToSummaryClasses[$changedProperty])) {
                $involvedSummaryClasses = [
                    ...$involvedSummaryClasses,
                    ...$this->propertyToSummaryClasses[$changedProperty],
                ];
            }
        }

        return array_values(array_unique($involvedSummaryClasses));
    }

    /**
     * Returns every summary class that depends on this source. Used when an
     * entity is created or deleted, which affects every summary of it.
     *
     * @return list<class-string>
     */
    public function getAllInvolvedSummaryClasses(): array
    {
        return $this->allInvolvedSummaryClasses;
    }
}
