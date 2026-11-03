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

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Metadata\Source\AssociationDependency;

/**
 * Walks a property path of a source entity through the Doctrine association
 * mappings, and returns a dependency for each related entity the path reads
 * a property of.
 *
 * Only to-one associations are followed. A to-many association on the path
 * makes the source entities affected by a change ambiguous, the rest of the
 * path is ignored.
 *
 * @internal
 */
final readonly class AssociationDependencyResolver
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {}

    /**
     * @param class-string $sourceClass
     * @param class-string $summaryClass
     * @return list<AssociationDependency>
     */
    public function resolve(
        string $sourceClass,
        string $path,
        string $summaryClass,
    ): array {
        $segments = array_map(trim(...), explode('.', trim($path)));
        $class = $sourceClass;

        // the path to the entity being walked, with the casts, null for the
        // source entity. the following segments are resolved from it
        $basePath = null;

        // the same, without the cast of its last segment. comparing the
        // association with the related entities does not need the cast. null
        // for the source entity
        $reversePath = null;

        // root entity cast, e.g. `(Class).prop`. it is kept in the reverse
        // paths, the associations after it may only exist in the subclass

        $rootCast = null;

        if (str_starts_with($segments[0], '(')) {
            $rootCast = array_shift($segments);
            $castClass = trim($rootCast, '() ');

            if (!class_exists($castClass)) {
                return [];
            }

            $class = $castClass;
        }

        $dependencies = [];

        while (($segment = array_shift($segments)) !== null) {
            // the alias of the entity, its identity and type do not change

            if ($segment === '*' || $segment === '') {
                break;
            }

            [$name, $cast] = $this->parseSegment($segment);
            $classMetadata = $this->getClassMetadata($class);

            if ($classMetadata === null) {
                break;
            }

            $isEmbedded = isset($classMetadata->embeddedClasses[$name]);
            $isAssociation = $classMetadata->hasAssociation($name);

            if (!$isEmbedded && !$isAssociation && !$classMetadata->hasField($name)) {
                break;
            }

            // the change of a collection is not reported in the change set
            // of the entity, and the source entities on the other side are
            // ambiguous

            if ($isAssociation && !$classMetadata->isSingleValuedAssociation($name)) {
                break;
            }

            // a property of a related entity. the properties of the source
            // entity itself are tracked by the source metadata

            if ($reversePath !== null) {
                // a field of an embeddable is reported as `embedded.field`

                $property = $isEmbedded
                    ? implode('.', [$name, ...$segments])
                    : $name;

                $dependencies[] = new AssociationDependency(
                    relatedClass: $class,
                    property: $property,
                    sourceClass: $sourceClass,
                    reversePath: $reversePath,
                    summaryClass: $summaryClass,
                );
            }

            if (!$isAssociation || $segments === []) {
                break;
            }

            $prefix = $basePath ?? $rootCast;
            $prefix = $prefix === null ? '' : $prefix . '.';

            $reversePath = $prefix . $name;
            $basePath = $prefix . $segment;

            /** @var class-string */
            $class = $cast ?? $classMetadata->getAssociationTargetClass($name);
        }

        return $dependencies;
    }

    /**
     * @return array{string,class-string|null}
     */
    private function parseSegment(string $segment): array
    {
        $position = strpos($segment, '(');

        if ($position === false) {
            return [$segment, null];
        }

        $name = trim(substr($segment, 0, $position));
        $cast = trim(substr($segment, $position + 1), ') ');

        return [$name, class_exists($cast) ? $cast : null];
    }

    /**
     * @param class-string $class
     * @return ClassMetadata<object>|null
     */
    private function getClassMetadata(string $class): ?ClassMetadata
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if ($manager === null) {
            return null;
        }

        $classMetadata = $manager->getClassMetadata($class);

        return $classMetadata instanceof ClassMetadata ? $classMetadata : null;
    }
}
