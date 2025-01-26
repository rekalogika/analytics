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

use Doctrine\ORM\Mapping\ClassMetadata;

final readonly class ClassMetadataWrapper
{
    /**
     * @param ClassMetadata<object> $classMetadata
     */
    public function __construct(private ClassMetadata $classMetadata) {}

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
        return $this->classMetadata->embeddedClasses[$property]['class'];
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
}
