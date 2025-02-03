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

namespace Rekalogika\Analytics\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Compatibility layer for Doctrine ClassMetadata. Abstracts the differences
 * between the different versions of Doctrine ORM.
 */
final readonly class ClassMetadataWrapper
{
    /**
     * @param ClassMetadata<object> $classMetadata
     */
    public function __construct(private ClassMetadata $classMetadata) {}

    /**
     * @param class-string $class
     */
    public static function get(
        EntityManagerInterface|ManagerRegistry $manager,
        string $class
    ): self {
        if ($manager instanceof ManagerRegistry) {
            $manager = $manager->getManagerForClass($class);
        }

        if (!$manager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException('Invalid manager');
        }

        $classMetadata = $manager->getClassMetadata($class);

        return new self($classMetadata);
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        $class = $this->classMetadata->getName();

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(\sprintf('Class "%s" not found', $class));
        }

        return $class;
    }

    public function isPropertyEntity(string $property): bool
    {
        return $this->classMetadata->hasAssociation($property);
    }

    public function isPropertyField(string $property): bool
    {
        return $this->classMetadata->hasField($property)
            && !$this->isPropertyEmbedded($property);
    }

    public function isPropertyEmbedded(string $property): bool
    {
        return isset($this->classMetadata->embeddedClasses[$property]);
    }

    /**
     * @return class-string
     */
    public function getEmbeddedClassOfProperty(string $property): string
    {
        $class = $this->classMetadata->embeddedClasses[$property]['class'] ?? null;

        if (!is_string($class) || !class_exists($class)) {
            throw new \InvalidArgumentException(\sprintf('Embedded class for property "%s" not found', $property));
        }

        return $class;
    }

    public function getIdentifierFieldName(): string
    {
        return $this->classMetadata->getSingleIdentifierFieldName();
    }

    public function getSQLTableName(): string
    {
        return $this->classMetadata->getTableName();
    }

    public function getSQLIdentifierFieldName(): string
    {
        return $this->classMetadata->getSingleIdentifierColumnName();
    }

    public function getSQLFieldName(string $property): string
    {
        if ($this->isPropertyField($property)) {
            return $this->classMetadata->getColumnName($property);
        } elseif ($this->isPropertyEntity($property)) {
            return $this->classMetadata->getSingleAssociationJoinColumnName($property);
        } else {
            throw new \InvalidArgumentException(\sprintf('Property "%s" not found', $property));
        }
    }

    /**
     * @return class-string
     */
    public function getAssociationTargetClass(string $property): string
    {
        $targetClass = $this->classMetadata->getAssociationTargetClass($property);

        if (!class_exists($targetClass)) {
            throw new \InvalidArgumentException(\sprintf('Target class for association "%s" not found', $property));
        }

        return $targetClass;
    }
}
