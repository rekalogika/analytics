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

namespace Rekalogika\Analytics\SummaryManager\Query\Path;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;

final class PathResolver
{
    private int $i = 0;

    /**
     * @var array<string,string>
     */
    private array $pathToAlias = [];

    /**
     * @var array<string,class-string>
     */
    private array $aliasToClass = [];

    private string $currentAlias;

    /**
     * @var class-string
     */
    private string $currentClass;

    /**
     * @param class-string $rootClass
     */
    public function __construct(
        private readonly string $rootClass,
        private readonly string $rootAlias,
        private readonly QueryBuilder $queryBuilder,
    ) {
        $this->reset();
    }

    public function reset(): void
    {
        $this->currentAlias = $this->rootAlias;
        $this->currentClass = $this->rootClass;
    }

    public function resolvePath(Path $path): string
    {
        try {
            while (true) {
                if (\count($path) === 1) {
                    return $this->processSinglePart($path);
                } elseif (\count($path) === 2) {
                    $result = $this->processTwoParts($path);

                    if ($result !== null) {
                        return $result;
                    }
                } elseif (\count($path) > 2) {
                    $this->processRelatedEntity($path);
                }
            }
        } finally {
            $this->reset();
        }
    }

    /**
     * Can be either a property, or alias to a related entity (if prefixed with *)
     */
    private function processSinglePart(Path $path): string
    {
        $part = $path->getFirstPart();

        if ($part->isAlias()) {
            return $this->processJoinAlias($path);
        } else {
            return $this->processSinglePartValue($path);
        }
    }

    private function processSinglePartValue(Path $path): string
    {
        return \sprintf('%s.%s', $this->currentAlias, $path->toString());
    }

    private function processJoinAlias(Path $path): string
    {
        $this->processRelatedEntity($path);

        return $this->currentAlias;
    }

    /**
     * Can be either: 1. a property in an embeddable, or 2. a a property in a
     * related entity
     */
    private function processTwoParts(Path $path): ?string
    {
        if ($this->hasProperty($path->toString())) { // an embeddable
            return $this->processSinglePartValue($path);
        } else {
            $this->processRelatedEntity($path);

            return null;
        }
    }

    private function processRelatedEntity(Path $path): void
    {
        // check cache

        $fullPath = $path->getFullPathToFirst(withCast: false);
        $cachedAlias = $this->pathToAlias[$fullPath] ?? null;

        if ($cachedAlias !== null) {
            $this->currentAlias = $cachedAlias;
            $this->currentClass = $this->aliasToClass[$cachedAlias]
                ?? throw new UnexpectedValueException(\sprintf(
                    'Alias "%s" not found',
                    $cachedAlias,
                ));

            if ($path->getFirstPart()->getCastToClass() !== null) {
                $this->processRelatedEntityWithCast($path);
            } else {
                $path->shift();
            }

            return;
        }

        // check the related class

        $first = $path->getFirstPart();
        $relatedClass = $this->getRelatedClass($first->getNameWithoutCast());

        if ($relatedClass === null) {
            throw new InvalidArgumentException(\sprintf(
                'Path "%s" on class "%s" does not point to a related entity',
                $first->getName(),
                $this->currentClass,
            ));
        }

        // join the related entity

        $fullPathWithoutCast = $path->getFullPathToFirst(withCast: false);
        $alias = $this->createAlias($fullPathWithoutCast, $relatedClass);

        $this->queryBuilder->leftJoin(
            \sprintf('%s.%s', $this->currentAlias, $first->getNameWithoutCast()),
            $alias,
        );

        $this->currentAlias = $alias;
        $this->currentClass = $relatedClass;
        $this->pathToAlias[$fullPathWithoutCast] = $alias;
        $this->aliasToClass[$alias] = $relatedClass;

        if ($path->getFirstPart()->getCastToClass() !== null) {
            $this->processRelatedEntityWithCast($path);
        } else {
            $path->shift();
        }
    }

    private function processRelatedEntityWithCast(Path $path): void
    {
        // check cache

        $fullPath = $path->getFullPathToFirst(withCast: true);
        $cachedAlias = $this->pathToAlias[$fullPath] ?? null;

        if ($cachedAlias !== null) {
            $this->currentAlias = $cachedAlias;
            $this->currentClass = $this->aliasToClass[$cachedAlias]
                ?? throw new UnexpectedValueException(\sprintf(
                    'Alias "%s" not found',
                    $cachedAlias,
                ));

            $path->shift();

            return;
        }

        // check the related class

        $first = $path->getFirstPart();
        $castToClass = $first->getCastToClass();

        if ($castToClass === null) {
            throw new InvalidArgumentException(\sprintf(
                'Path "%s" on class "%s" does not have a cast to class',
                $first->getName(),
                $this->currentClass,
            ));
        }

        $fullPathWithCast = $path->getFullPathToFirst(withCast: false);
        $alias = $this->createAlias($fullPathWithCast, $castToClass);
        $idProperty = $this->getIdOfClass($this->currentClass);

        $this->queryBuilder->leftJoin(
            $castToClass,
            $alias,
            'WITH',
            \sprintf(
                '%s.%s = %s.%s',
                $alias,
                $idProperty,
                $this->currentAlias,
                $idProperty,
            ),
        );

        $this->currentAlias = $alias;
        $this->currentClass = $castToClass;
        $this->pathToAlias[$fullPathWithCast] = $alias;
        $this->aliasToClass[$alias] = $castToClass;

        $path->shift();
    }

    /**
     * @return ClassMetadata<object>
     */
    private function getClassMetadata(): ClassMetadata
    {
        return $this->queryBuilder->getEntityManager()
            ->getClassMetadata($this->currentClass);
    }

    /**
     * @param class-string $class
     */
    private function getIdOfClass(string $class): string
    {
        $metadata = $this->queryBuilder->getEntityManager()
            ->getClassMetadata($class);

        return $metadata->getSingleIdentifierFieldName();
    }

    /**
     * @return null|class-string
     */
    private function getRelatedClass(string $property): ?string
    {
        $metadata = $this->getClassMetadata();

        if ($metadata->hasAssociation($property)) {
            return $metadata->getAssociationTargetClass($property);
        }

        return null;
    }

    private function hasProperty(string $property): bool
    {
        return $this->getClassMetadata()->hasField($property);
    }

    /**
     * @param class-string $class
     */
    private function createAlias(string $fullPath, string $class): string
    {
        $alias = '_a' . $this->i++;
        $this->pathToAlias[$fullPath] = $alias;
        $this->aliasToClass[$alias] = $class;

        return $alias;
    }
}
