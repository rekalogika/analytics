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

namespace Rekalogika\Analytics\UX\PanelBundle\Internal;

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class PivotTableItem
{
    public function __construct(
        private DimensionMetadata|MeasureMetadata|PivotTableValues $property,
    ) {}

    public function getKey(): string
    {
        return $this->property->getName();
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->property->getLabel();
    }

    public function getType(): string
    {
        return $this->getTypeEnum()->value;
    }

    public function getTypeEnum(): PivotTableItemType
    {
        if ($this->property instanceof DimensionMetadata) {
            if ($this->property->isMandatory()) {
                return PivotTableItemType::MandatoryDimension;
            } else {
                return PivotTableItemType::Dimension;
            }
        } elseif ($this->property instanceof MeasureMetadata) {
            return PivotTableItemType::Measure;
        } else {
            return PivotTableItemType::Values;
        }
    }

    /**
     * @return null|list<PivotTableItemOptionGroup|PivotTableItemOption>
     */
    public function getChoices(): null|array
    {
        if (!$this->property instanceof DimensionMetadata) {
            return null;
        }

        $options = [];

        foreach ($this->property->getLeaves() as $leaf) {
            $option = new PivotTableItemOption($leaf);
            $optGroup = PivotTableItemOptionGroup::create($leaf);

            if ($optGroup === null) {
                // If the option is not part of a group, we can return it directly
                $options[] = $option;
                continue;
            }

            $optGroupId = $option->getOptGroupId();

            if (!isset($options[$optGroupId])) {
                $options[$optGroupId] = $optGroup;
            }

            /** @var PivotTableItemOptionGroup */
            $optGroup = $options[$optGroupId];

            $optGroup->addOption($option);
        }

        return array_values($options);
    }
}
