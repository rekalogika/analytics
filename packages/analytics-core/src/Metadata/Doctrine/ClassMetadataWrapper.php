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
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;

/**
 * Compatibility layer for Doctrine ClassMetadata. Abstracts the differences
 * between the different versions of Doctrine ORM, and adds the type checks
 * needed for static analysis.
 *
 * The framework uses it to inspect both the source and the summary entities:
 * which properties are fields, associations, or embeddables, their column
 * names, and their types.
 */
final readonly class ClassMetadataWrapper
{
    /**
     * @var ClassMetadata<object>
     */
    private ClassMetadata $classMetadata;

    private EntityManagerInterface $manager;

    /**
     * @param ObjectManager|ManagerRegistry|null $manager The entity manager
     * of the class, or a registry to look it up in.
     * @param class-string $class The entity class.
     * @throws UnexpectedValueException If no entity manager is available for
     * the class.
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

    /**
     * Returns the metadata of the nearest mapped parent class, or null if the
     * class has no mapped parent.
     */
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
     * The entity class this metadata describes.
     *
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

    /**
     * Returns true if the class is an entity, not a mapped superclass or an
     * embeddable.
     */
    public function isEntity(): bool
    {
        return !$this->classMetadata->isMappedSuperclass
            && !$this->classMetadata->isEmbeddedClass;
    }

    /**
     * Returns true if the property is mapped, either as a scalar field or as
     * an association. Embedded properties are not included.
     */
    public function hasProperty(string $property): bool
    {
        return $this->isPropertyField($property) || $this->isPropertyEntity($property);
    }

    /**
     * Returns true if the property is an association to another entity.
     */
    public function isPropertyEntity(string $property): bool
    {
        return $this->classMetadata->hasAssociation($property);
    }

    /**
     * Returns true if the property is a scalar field, i.e. mapped to a column
     * and not an embeddable.
     */
    public function isPropertyField(string $property): bool
    {
        return $this->classMetadata->hasField($property)
            && !$this->isPropertyEmbedded($property);
    }

    /**
     * Returns true if the property is an embeddable.
     */
    public function isPropertyEmbedded(string $property): bool
    {
        return isset($this->classMetadata->embeddedClasses[$property]);
    }

    /**
     * Returns the class of the embeddable in the property.
     *
     * @return class-string
     * @throws MetadataException If the property is not an embeddable.
     */
    public function getEmbeddedClassOfProperty(string $property): string
    {
        $class = $this->classMetadata->embeddedClasses[$property]['class'] ?? null;

        if (!\is_string($class) || !class_exists($class)) {
            throw new MetadataException(\sprintf('Embedded class for property "%s" not found', $property));
        }

        return $class;
    }

    /**
     * The name of the identifier property. The entity must have a single
     * identifier.
     */
    public function getIdentifierFieldName(): string
    {
        return $this->classMetadata->getSingleIdentifierFieldName();
    }

    /**
     * Returns the identifier of the entity as a string.
     *
     * @throws MetadataException If the entity has a composite identifier, no
     * identifier value, or an identifier that is not a string, a number, or
     * a `Stringable` object such as a `Uuid`.
     */
    public function getStringIdentifierFromObject(object $entity): string
    {
        $identifierValues = $this->classMetadata->getIdentifierValues($entity);

        if (\count($identifierValues) > 1) {
            throw new MetadataException('Entity has multiple identifiers, cannot return a single identifier value.');
        }

        /** @psalm-suppress MixedAssignment */
        $id = reset($identifierValues);

        if ($id === false) {
            throw new MetadataException('Entity does not have an identifier value.');
        }

        if (\is_string($id) || is_numeric($id) || $id instanceof \Stringable) {
            return (string) $id;
        }

        throw new MetadataException(\sprintf(
            'Identifier value for entity "%s" cannot be converted to string, got "%s".',
            $this->getClass(),
            get_debug_type($id),
        ));
    }

    /**
     * The name of the database table of the entity.
     */
    public function getSQLTableName(): string
    {
        return $this->classMetadata->getTableName();
    }

    /**
     * The name of the identifier column. The entity must have a single
     * identifier.
     */
    public function getSQLIdentifierFieldName(): string
    {
        return $this->classMetadata->getSingleIdentifierColumnName();
    }

    /**
     * Returns the name of the column of the property. For an association, the
     * name of its join column. Properties inside embeddables are referred to
     * using dot notation, e.g. `partition.key`.
     *
     * @throws MetadataException If the property is neither a field nor an
     * association.
     */
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
     * Returns the entity class the association property points to.
     *
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
     * Returns the DBAL type name of the field, e.g. `integer`, or null if the
     * mapping does not specify one.
     */
    public function getScalarType(string $property): ?string
    {
        $fieldMapping = $this->classMetadata->getFieldMapping($property);

        if (!isset($fieldMapping['type'])) {
            return null;
        }

        $type = $fieldMapping['type'];

        if (!\is_string($type)) {
            throw new MetadataException(\sprintf('Type for property "%s" is not a string', $property));
        }

        return $type;
    }

    /**
     * Returns the enum class the field is mapped to, or null if the field is
     * not an enum.
     *
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
}
