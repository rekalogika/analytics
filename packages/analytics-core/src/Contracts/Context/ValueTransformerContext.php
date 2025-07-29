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

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
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
        }

        throw new LogicException(\sprintf(
            'Property metadata must be an instance of "%s", got "%s".',
            DimensionMetadata::class,
            $this->propertyMetadata::class,
        ));
    }
}
