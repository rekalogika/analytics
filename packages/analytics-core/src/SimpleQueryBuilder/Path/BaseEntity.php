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

namespace Rekalogika\Analytics\SimpleQueryBuilder\Path;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Exception\LogicException;

final readonly class BaseEntity
{
    /**
     * @param class-string $baseClass
     */
    public function __construct(
        private string $basePath,
        private string $baseClass,
        private string $baseAlias,
        private QueryBuilder $queryBuilder,
        private PathContext $context,
    ) {
        $context->addBaseEntity($this);
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function resolve(Path $path): string
    {
        $count = \count($path);

        /** @psalm-suppress TypeDoesNotContainType */
        if ($count === 0) {
            throw new LogicException('Path is empty, cannot resolve');
        }

        if ($count === 1) {
            return $this->processHasOneElement($path);
        }

        if ($count === 2) {
            return $this->processHasTwoElements($path);
        }

        return $this->processHasMoreElements($path);
    }

    /**
     * If we have only one element, the possibilities are:
     *
     * - It is a property
     * - It is the entity alias, if the element is an asterisk
     */
    private function processHasOneElement(Path $path): string
    {
        $element = $path->getFirstElement();

        if ($element instanceof Alias) {
            return $this->baseAlias;
        }

        return $this->processProperty($path);
    }

    /**
     * If we have two elements, the possibilities are:
     *
     * - It is a property of an embeddable in the base entity
     * - It is a property in a related entity
     */
    private function processHasTwoElements(Path $path): string
    {
        if ($this->hasProperty((string) $path)) {
            return $this->processProperty($path);
        } else {
            return $this->processRelatedEntity($path);
        }
    }

    /**
     * If we have more than two elements, the first element must be a reference
     * to a related entity
     */
    private function processHasMoreElements(Path $path): string
    {
        return $this->processRelatedEntity($path);
    }

    /**
     * Executed if the path is a property.
     *
     * - If we have one element, it is a property of the base entity
     * - If we have two elements, it is a property in an embeddable in the base
     *   entity
     */
    private function processProperty(Path $path): string
    {
        return \sprintf(
            '%s.%s',
            $this->baseAlias,
            (string) $path,
        );
    }

    /**
     * Executed if it is known that the first element must be a reference to a
     * related entity.
     */
    private function processRelatedEntity(Path $path): string
    {
        [$firstElement, $subPath] = $path->shift();

        if (!$firstElement instanceof PathElement) {
            throw new LogicException(\sprintf(
                'First element of the path "%s" must be a PathElement, got %s',
                $path,
                get_debug_type($firstElement),
            ));
        }

        if ($firstElement->getClassCast() === null) {
            $baseEntity = $this->createBaseEntityWithoutCast($firstElement);
        } else {
            $baseEntity = $this->createBaseEntityWithCast($firstElement);
        }

        return $baseEntity->resolve($subPath);
    }

    private function createBaseEntityWithoutCast(
        PathElement $firstElement,
    ): BaseEntity {
        $basePath = $this->basePath . '.' . $firstElement->getName();
        $basePath = trim($basePath, '.');

        if (($cachedEntity = $this->context->getBaseEntityFromCache($basePath)) !== null) {
            return $cachedEntity;
        }

        $baseClass = $this->getRelatedClass($firstElement->getName());
        $baseAlias = $this->context->getAlias($basePath);

        $this->queryBuilder->leftJoin(
            \sprintf('%s.%s', $this->baseAlias, $firstElement->getName()),
            $baseAlias,
        );

        $baseEntity = new self(
            basePath: $basePath,
            baseClass: $baseClass,
            baseAlias: $baseAlias,
            queryBuilder: $this->queryBuilder,
            context: $this->context,
        );

        return $baseEntity;
    }

    private function createBaseEntityWithCast(
        PathElement $firstElement,
    ): BaseEntity {
        $basePath = $this->basePath . '.' . (string) $firstElement;
        $basePath = trim($basePath, '.');

        if (($cachedEntity = $this->context->getBaseEntityFromCache($basePath)) !== null) {
            return $cachedEntity;
        }

        $classCast = $firstElement->getClassCast()
            ?? throw new LogicException('Cast to class is not set');

        $baseEntityWithoutCast = $this->createBaseEntityWithoutCast($firstElement);
        $castedAlias = $this->context->getAlias($basePath);
        $idProperty = $this->getIdOfClass($classCast);

        $this->queryBuilder->leftJoin(
            $classCast,
            $castedAlias,
            'WITH',
            \sprintf(
                '%s.%s = %s.%s',
                $castedAlias,
                $idProperty,
                $baseEntityWithoutCast->baseAlias,
                $idProperty,
            ),
        );

        $baseEntity = new self(
            basePath: $basePath,
            baseClass: $classCast,
            baseAlias: $castedAlias,
            queryBuilder: $this->queryBuilder,
            context: $this->context,
        );

        return $baseEntity;
    }

    /**
     * @return ClassMetadata<object>
     */
    private function getClassMetadata(): ClassMetadata
    {
        // @phpstan-ignore argument.templateType
        return $this->queryBuilder->getEntityManager()
            ->getClassMetadata($this->baseClass);
    }

    private function hasProperty(string $property): bool
    {
        return $this->getClassMetadata()->hasField($property);
    }

    /**
     * @return class-string
     */
    private function getRelatedClass(string $property): string
    {
        try {
            return  $this->getClassMetadata()
                ->getAssociationTargetClass($property);
        } catch (\InvalidArgumentException $e) {
            throw new LogicException(
                \sprintf('Property "%s" is not a relation', $property),
                previous: $e,
            );
        }
    }

    /**
     * @param class-string $class
     */
    private function getIdOfClass(string $class): string
    {
        return $this->getClassMetadata()->getSingleIdentifierFieldName();
    }
}
