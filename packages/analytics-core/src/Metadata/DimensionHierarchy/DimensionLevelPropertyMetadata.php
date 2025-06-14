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

namespace Rekalogika\Analytics\Metadata\DimensionHierarchy;

use Rekalogika\Analytics\Contracts\Hierarchy\HierarchyAware;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Core\Exception\MetadataException;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionLevelPropertyMetadata
{
    /**
     * @param null|class-string $typeClass
     */
    public function __construct(
        private string $name,
        private TranslatableInterface $label,
        private ValueResolver&HierarchyAware $valueResolver,
        private ?string $typeClass,
        private TranslatableInterface $nullLabel,
        private bool $hidden,
        private ?DimensionLevelMetadata $levelMetadata = null,
    ) {}

    public function withLevelMetadata(DimensionLevelMetadata $levelMetadata): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            valueResolver: $this->valueResolver,
            typeClass: $this->typeClass,
            nullLabel: $this->nullLabel,
            hidden: $this->hidden,
            levelMetadata: $levelMetadata,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    public function getLevelMetadata(): DimensionLevelMetadata
    {
        if ($this->levelMetadata === null) {
            throw new MetadataException('Level metadata is not set');
        }

        return $this->levelMetadata;
    }

    public function getValueResolver(): ValueResolver&HierarchyAware
    {
        return $this->valueResolver;
    }

    /**
     * @return class-string|null
     */
    public function getTypeClass(): ?string
    {
        return $this->typeClass;
    }

    public function getNullLabel(): TranslatableInterface
    {
        return $this->nullLabel;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
