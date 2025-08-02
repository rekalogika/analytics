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
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultDimension;

final class DimensionCollection
{
    /**
     * @var array<string,DimensionFieldCollection>
     */
    private array $dimensionFieldCollections = [];

    public function __construct(
        private readonly DimensionFactory $dimensionFactory,
        private readonly OrderByResolver $orderByResolver,
    ) {}

    public function collectDimension(DefaultDimension $dimension): void
    {
        $this
            ->getDimensionsByName($dimension->getName())
            ->collectDimension($dimension);
    }

    public function getDimensionsByName(string $name): DimensionFieldCollection
    {
        return $this->dimensionFieldCollections[$name]
            ??= new DimensionFieldCollection(
                name: $name,
                order: $this->getOrder($name),
                dimensionFactory: $this->dimensionFactory,
            );
    }

    private function getOrder(string $name): ?Order
    {
        return $this->orderByResolver->getDimensionNameOrderBy($name);
    }
}
