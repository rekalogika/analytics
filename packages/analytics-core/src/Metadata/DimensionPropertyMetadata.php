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

namespace Rekalogika\Analytics\Metadata;

use Rekalogika\Analytics\DimensionValueResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionPropertyMetadata
{
    public function __construct(
        private string $name,
        private string $hierarchyName,
        private string|TranslatableInterface $label,
        private DimensionValueResolver $valueResolver,
        private ?DimensionLevelMetadata $levelMetadata = null,
    ) {}

    public function withLevelMetadata(DimensionLevelMetadata $levelMetadata): self
    {
        return new self(
            name: $this->name,
            hierarchyName: $this->hierarchyName,
            label: $this->label,
            valueResolver: $this->valueResolver,
            levelMetadata: $levelMetadata,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHierarchyName(): string
    {
        return $this->hierarchyName;
    }

    public function getFullName(): string
    {
        return $this->hierarchyName . '.' . $this->name;
    }

    public function getLabel(): string|TranslatableInterface
    {
        return $this->label;
    }

    public function getLevelMetadata(): DimensionLevelMetadata
    {
        if ($this->levelMetadata === null) {
            throw new \LogicException('Level metadata is not set');
        }

        return $this->levelMetadata;
    }

    public function getValueResolver(): DimensionValueResolver
    {
        return $this->valueResolver;
    }
}
