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
 * through which of its properties. Also, which summaries of other classes
 * read its properties through an association. The refresh engine uses it to
 * decide which summaries to mark as outdated when an entity is created,
 * updated, or deleted.
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
     * @param array<string,list<AssociationDependency>> $propertyToAssociationDependencies
     * For each property of the class, the summaries of other classes that
     * read it through an association.
     * @param list<AssociationDependency> $existenceDependencies The summaries
     * of other classes whose source entities refer to entities of this class.
     * @param list<ToManyPath> $toManyPaths The paths of the summaries of this
     * class read through a to-many association, which are not tracked
     * beyond it.
     */
    public function __construct(
        private string $class,
        private array $propertyToSummaryClasses,
        private array $propertyToAssociationDependencies = [],
        private array $existenceDependencies = [],
        private array $toManyPaths = [],
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

    /**
     * For each property of the class, the summaries of other classes that
     * read it through an association. These summaries are not summaries of
     * this class, and are not returned by the other methods.
     *
     * @return array<string,list<AssociationDependency>>
     */
    public function getPropertyToAssociationDependencies(): array
    {
        return $this->propertyToAssociationDependencies;
    }

    /**
     * Returns the association dependencies affected by a change to the given
     * properties of an entity of this class.
     *
     * @param list<string> $changedProperties
     * @return list<AssociationDependency>
     */
    public function getAssociationDependenciesByChangedProperties(
        array $changedProperties,
    ): array {
        $dependencies = [];

        foreach ($changedProperties as $changedProperty) {
            foreach ($this->propertyToAssociationDependencies[$changedProperty] ?? [] as $dependency) {
                $dependencies[$dependency->getSignature()] = $dependency;
            }
        }

        return array_values($dependencies);
    }

    /**
     * The summaries of other classes whose source entities refer to entities
     * of this class, through an association. The dependencies have no
     * property.
     *
     * @return list<AssociationDependency>
     */
    public function getExistenceDependencies(): array
    {
        return $this->existenceDependencies;
    }

    /**
     * Returns every association dependency of this class, including the
     * existence dependencies. Used when an entity of this class is deleted,
     * which affects every source entity that refers to it.
     *
     * @return list<AssociationDependency>
     */
    public function getAllAssociationDependencies(): array
    {
        $dependencies = [];

        $allDependencies = [
            ...$this->getAssociationDependenciesByChangedProperties(
                array_keys($this->propertyToAssociationDependencies),
            ),
            ...$this->existenceDependencies,
        ];

        foreach ($allDependencies as $dependency) {
            $dependencies[$dependency->getSignature()] = $dependency;
        }

        return array_values($dependencies);
    }

    /**
     * The paths of the summaries of this class read through a to-many
     * association. A change read through the to-many association is not
     * detected.
     *
     * @return list<ToManyPath>
     */
    public function getToManyPaths(): array
    {
        return $this->toManyPaths;
    }
}
