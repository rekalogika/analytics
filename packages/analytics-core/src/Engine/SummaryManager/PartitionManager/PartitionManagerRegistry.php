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

namespace Rekalogika\Analytics\Engine\SummaryManager\PartitionManager;

use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class PartitionManagerRegistry
{
    public function __construct(
        private readonly SummaryMetadataFactory $metadataFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {}

    /**
     * @param class-string $class
     */
    public function createPartitionManager(string $class): PartitionManager
    {
        $metadata = $this->metadataFactory->getSummaryMetadata($class);

        return new PartitionManager(
            metadata: $metadata,
            propertyAccessor: $this->propertyAccessor,
        );
    }
}
