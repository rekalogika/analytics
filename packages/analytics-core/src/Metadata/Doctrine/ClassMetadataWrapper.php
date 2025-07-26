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

namespace Rekalogika\Analytics\Metadata\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;

/**
 * Compatibility layer for Doctrine ClassMetadata. Abstracts the differences
 * between the different versions of Doctrine ORM.
 */
final readonly class ClassMetadataWrapper
{
    /**
     * @var ClassMetadata<object>
     */
    private ClassMetadata $classMetadata;

    private EntityManagerInterface $manager;

    /**
     * @param class-string $class
     */
    public function __construct(
        ObjectManager|ManagerRegistry|null $manager,
        string $class,
    ) {
        if ($manager === null) {
            throw new UnexpectedValueException('Manager is not found.');
        }

        if ($manager instanceof ManagerRegistry) {
            $manager = $manager->getManagerForClass($class);

            if (!$manager instanceof EntityManagerInterface) {
                throw new UnexpectedValueException(\sprintf(
                    'ManagerRegistry does not have a manager for class "%s"',
                    $class,
                ));
            }
        }

        if (!$manager instanceof EntityManagerInterface) {
            throw new UnexpectedValueException(\sprintf(
                'Expected an instance of EntityManagerInterface, got "%s"',
                get_debug_type($manager),
            ));
        }

        $this->manager = $manager;
        $this->classMetadata = $this->manager->getClassMetadata($class);
    }

    public function getParent(): ?self
    {
        $parentClass = $this->classMetadata->parentClasses[0] ?? null;

        if ($parentClass === null) {
            return null;
        }

        if (!class_exists($parentClass)) {
            throw new MetadataException(\sprintf('Parent class "%s" not found', $parentClass));
        }

        return new self($this->manager, $parentClass);
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        $class = $this->classMetadata->getName();

        if (!class_exists($class)) {
            throw new UnexpectedValueException(\sprintf('Class "%s" not found', $class));
        }

        return $class;
    }

    public function hasProperty(string $property): bool
    {
        return $this->isPropertyField($property) || $this->isPropertyEntity($property);
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

        if (!\is_string($class) || !class_exists($class)) {
            throw new MetadataException(\sprintf('Embedded class for property "%s" not found', $property));
        }

        return $class;
    }

    public function getIdentifierFieldName(): string
    {
        return $this->classMetadata->getSingleIdentifierFieldName();
    }

    public function getStringIdentifierFromObject(object $entity): string
    {
        $identifierValues = $this->classMetadata->getIdentifierValues($entity);

        if (\count($identifierValues) > 1) {
            throw new MetadataException('Entity has multiple identifiers, cannot return a single identifier value.');
        }

        /** @psalm-suppress MixedAssignment */
        $firstValue = reset($identifierValues);

        if ($firstValue === false) {
            throw new MetadataException('Entity does not have an identifier value.');
        }

        /** @psalm-suppress MixedArgument */
        return \strval($firstValue);
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
            throw new MetadataException(\sprintf('Property "%s" not found', $property));
        }
    }

    /**
     * @return class-string
     */
    public function getAssociationTargetClass(string $property): string
    {
        $targetClass = $this->classMetadata->getAssociationTargetClass($property);

        if (!class_exists($targetClass)) {
            throw new MetadataException(\sprintf('Target class for association "%s" not found', $property));
        }

        return $targetClass;
    }

    /**
     * @return class-string<\UnitEnum>|null
     */
    public function getEnumType(string $property): ?string
    {
        $fieldMapping = $this->classMetadata->getFieldMapping($property);

        $enumType = $fieldMapping['enumType'] ?? null;

        if ($enumType === null) {
            return null;
        }

        if (!\is_string($enumType) || !enum_exists($enumType)) {
            throw new MetadataException(\sprintf('Enum type "%s" not found', get_debug_type($enumType)));
        }

        /** @var class-string<\UnitEnum> */
        return $enumType;
    }

    public function getIdReflectionProperty(): \ReflectionProperty
    {
        $reflection = $this->classMetadata->getSingleIdReflectionProperty();

        if (!$reflection instanceof \ReflectionProperty) {
            throw new MetadataException('Reflection property not found');
        }

        return $reflection;
    }
}
