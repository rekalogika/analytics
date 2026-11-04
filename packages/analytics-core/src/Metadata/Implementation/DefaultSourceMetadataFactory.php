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

use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Metadata\Source\AssociationDependency;
use Rekalogika\Analytics\Metadata\Source\SourceMetadata;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Source\ToManyPath;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

/**
 * Default implementation of SourceMetadataFactory. On construction, it goes
 * through every summary class, and inverts the relationship: from each source
 * class and each of its properties, to the summary classes that depend on
 * that property. It also follows the paths through associations, from each
 * related class and each of its properties, to the summaries that read them.
 */
final readonly class DefaultSourceMetadataFactory implements SourceMetadataFactory
{
    /**
     * @var array<class-string,array<string,list<class-string>>>
     */
    private array $involvedProperties;

    /**
     * @var array<class-string,array<string,list<AssociationDependency>>>
     */
    private array $associationDependencies;

    /**
     * @var array<class-string,list<AssociationDependency>>
     */
    private array $existenceDependencies;

    /**
     * @var array<class-string,list<ToManyPath>>
     */
    private array $toManyPaths;

    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
        ManagerRegistry $managerRegistry,
    ) {
        $this->involvedProperties = $this->createInvolvedProperties();
        $resolver = new AssociationDependencyResolver($managerRegistry);

        [$this->associationDependencies, $this->existenceDependencies] =
            $this->createAssociationDependencies($resolver);

        $this->toManyPaths = $this->createToManyPaths($resolver);
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
            $properties = $summaryMetadata->getInvolvedSourceProperties();

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

    /**
     * Related class to the mapping of its properties to the summaries of
     * other classes that read them through an association. And related class
     * to the summaries of other classes whose source entities refer to it.
     *
     * @return array{
     *     array<class-string,array<string,list<AssociationDependency>>>,
     *     array<class-string,list<AssociationDependency>>
     * }
     */
    private function createAssociationDependencies(
        AssociationDependencyResolver $resolver,
    ): array {
        /** @var array<class-string,array<string,array<string,AssociationDependency>>> */
        $dependencies = [];

        /** @var array<class-string,array<string,AssociationDependency>> */
        $existenceDependencies = [];

        foreach ($this->getAllSummaryMetadata() as $summaryMetadata) {
            if (!$summaryMetadata->isTrackAssociations()) {
                continue;
            }

            foreach ($summaryMetadata->getInvolvedSourcePaths() as $path) {
                $pathDependencies = $resolver->resolve(
                    sourceClass: $summaryMetadata->getSourceClass(),
                    path: $path,
                    summaryClass: $summaryMetadata->getSummaryClass(),
                );

                foreach ($pathDependencies as $dependency) {
                    $relatedClass = $dependency->getRelatedClass();
                    $property = $dependency->getProperty();
                    $signature = $dependency->getSignature();

                    if ($property === null) {
                        $existenceDependencies[$relatedClass][$signature] = $dependency;
                    } else {
                        $dependencies[$relatedClass][$property][$signature] = $dependency;
                    }
                }
            }
        }

        $result = [];

        foreach ($dependencies as $relatedClass => $properties) {
            foreach ($properties as $property => $propertyDependencies) {
                $result[$relatedClass][$property] = array_values($propertyDependencies);
            }
        }

        $existenceResult = [];

        foreach ($existenceDependencies as $relatedClass => $classDependencies) {
            $existenceResult[$relatedClass] = array_values($classDependencies);
        }

        return [$result, $existenceResult];
    }

    /**
     * Source class to the paths of its summaries read through a to-many
     * association. The summaries opting out of association tracking are
     * left out, none of their related paths are tracked.
     *
     * @return array<class-string,list<ToManyPath>>
     */
    private function createToManyPaths(
        AssociationDependencyResolver $resolver,
    ): array {
        $toManyPaths = [];

        foreach ($this->getAllSummaryMetadata() as $summaryMetadata) {
            if (!$summaryMetadata->isTrackAssociations()) {
                continue;
            }

            $sourceClass = $summaryMetadata->getSourceClass();

            foreach ($summaryMetadata->getInvolvedSourcePaths() as $path) {
                $association = $resolver->findToManyAssociation($sourceClass, $path);

                if ($association === null) {
                    continue;
                }

                $toManyPaths[$sourceClass][] = new ToManyPath(
                    sourceClass: $sourceClass,
                    path: $path,
                    association: $association,
                    summaryClass: $summaryMetadata->getSummaryClass(),
                );
            }
        }

        return $toManyPaths;
    }

    /**
     * Also includes the summaries of the parent classes of the source, so
     * that a change to an entity of a subclass is detected by a summary
     * declared on the superclass.
     */
    #[\Override]
    public function getSourceMetadata(string $sourceClassName): SourceMetadata
    {
        $allPropertiesToSummaryClasses = [];

        $parents = class_parents($sourceClassName);

        if ($parents === false) {
            $parents = [];
        }

        $classes = [$sourceClassName, ...$parents];

        $allPropertiesToAssociationDependencies = [];
        $allExistenceDependencies = [];
        $allToManyPaths = [];

        foreach ($classes as $class) {
            foreach ($this->involvedProperties[$class] ?? [] as $property => $summaryClasses) {
                foreach ($summaryClasses as $summaryClass) {
                    $allPropertiesToSummaryClasses[$property][] = $summaryClass;
                }
            }

            foreach ($this->associationDependencies[$class] ?? [] as $property => $dependencies) {
                foreach ($dependencies as $dependency) {
                    $allPropertiesToAssociationDependencies[$property][] = $dependency;
                }
            }

            foreach ($this->existenceDependencies[$class] ?? [] as $dependency) {
                $allExistenceDependencies[] = $dependency;
            }

            foreach ($this->toManyPaths[$class] ?? [] as $toManyPath) {
                $allToManyPaths[] = $toManyPath;
            }
        }

        return new SourceMetadata(
            class: $sourceClassName,
            propertyToSummaryClasses: $allPropertiesToSummaryClasses,
            propertyToAssociationDependencies: $allPropertiesToAssociationDependencies,
            existenceDependencies: $allExistenceDependencies,
            toManyPaths: $allToManyPaths,
        );
    }
}
