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

namespace Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class MetadataOrderByResolver implements OrderByResolver
{
    public function __construct(
        private readonly SummaryMetadata $metadata,
        private readonly DefaultQuery $query,
    ) {}

    #[\Override]
    public function getDimensionNameOrderBy(string $name): ?Order
    {
        $orderBy = $this->query->getOrderBy();

        foreach ($orderBy as $dimensionName => $order) {
            if ($dimensionName === $name) {
                return $order;
            }
        }

        try {
            $metadata = $this->metadata->getDimension($name);
        } catch (MetadataException) {
            $metadata = null;
        }

        if ($metadata !== null) {
            $order = $metadata->getOrderBy();

            if ($order instanceof Order) {
                return $order;
            }
        }

        return null;
    }
}
