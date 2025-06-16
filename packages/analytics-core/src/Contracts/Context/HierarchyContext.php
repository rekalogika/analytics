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

namespace Rekalogika\Analytics\Contracts\Context;

use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionPropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\PropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class HierarchyContext
{
    public function __construct(
        private SummaryMetadata $summaryMetadata,
        private DimensionMetadata $dimensionMetadata,
        private DimensionHierarchyMetadata $dimensionHierarchyMetadata,
        private PropertyMetadata $propertyMetadata,
    ) {}

    public function getSummaryMetadata(): SummaryMetadata
    {
        return $this->summaryMetadata;
    }

    public function getDimensionMetadata(): DimensionMetadata
    {
        return $this->dimensionMetadata;
    }

    public function getDimensionHierarchyMetadata(): DimensionHierarchyMetadata
    {
        return $this->dimensionHierarchyMetadata;
    }

    public function getPropertyMetadata(): PropertyMetadata
    {
        return $this->propertyMetadata;
    }

    public function getUserValue(string $property, mixed $rawValue): mixed
    {
        $propertyMetadata = $this->propertyMetadata;

        if (
            !$propertyMetadata instanceof DimensionPropertyMetadata
            && !$propertyMetadata instanceof DimensionMetadata
        ) {
            throw new InvalidArgumentException(\sprintf(
                'User value is only supported for dimensions, but property "%s" is given.',
                $property,
            ));
        }

        $valueResolver = $propertyMetadata->getValueResolver();

        if (!$valueResolver instanceof UserValueTransformer) {
            return $rawValue;
        }

        $timeZone = $propertyMetadata->getSummaryTimeZone();

        return $this->dimensionMetadata->getUserValue($rawValue);
    }
}
