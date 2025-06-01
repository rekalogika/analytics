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

namespace Rekalogika\Analytics\Metadata\Summary;

use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionLevelPropertyMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionPropertyMetadata extends PropertyMetadata
{
    /**
     * @param class-string|null $typeClass
     */
    public function __construct(
        string $summaryProperty,
        string $hierarchyProperty,
        TranslatableInterface $label,
        private TranslatableInterface $nullLabel,
        private ?string $typeClass,
        private DimensionLevelPropertyMetadata $dimensionLevelProperty,
        SummaryMetadata $summaryMetadata,
    ) {
        parent::__construct(
            summaryProperty: \sprintf('%s.%s', $summaryProperty, $hierarchyProperty),
            label: $label,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function getDimensionLevelProperty(): DimensionLevelPropertyMetadata
    {
        return $this->dimensionLevelProperty;
    }

    public function getNullLabel(): TranslatableInterface
    {
        return $this->nullLabel;
    }

    /**
     * @return null|class-string
     */
    public function getTypeClass(): ?string
    {
        return $this->typeClass;
    }
}
