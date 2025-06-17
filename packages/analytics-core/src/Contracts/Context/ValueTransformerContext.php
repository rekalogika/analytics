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

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionPropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\PropertyMetadata;

final readonly class ValueTransformerContext
{
    public function __construct(
        private PropertyMetadata $propertyMetadata,
    ) {}

    public function getPropertyMetadata(): PropertyMetadata
    {
        return $this->propertyMetadata;
    }

    public function getDimensionMetadata(): DimensionMetadata
    {
        if ($this->propertyMetadata instanceof DimensionMetadata) {
            return $this->propertyMetadata;
        } elseif ($this->propertyMetadata instanceof DimensionPropertyMetadata) {
            return $this->propertyMetadata->getDimension();
        }

        throw new LogicException(\sprintf(
            'Property metadata must be an instance of %s or %s, got %s',
            DimensionMetadata::class,
            DimensionPropertyMetadata::class,
            \get_class($this->propertyMetadata),
        ));
    }
}
