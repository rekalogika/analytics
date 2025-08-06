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

use Rekalogika\Analytics\Contracts\Exception\InterpolationOverflowException;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final class DimensionFactory
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions = [];

    private int $currentNodesCount = 0;

    private readonly DimensionCollection $dimensionCollection;

    public function __construct(
        private readonly OrderByResolver $orderByResolver,
        private int $nodesLimit,
    ) {
        $this->dimensionCollection = new DimensionCollection(
            dimensionFactory: $this,
            orderByResolver: $this->orderByResolver,
        );
    }

    public function getDimensionCollection(): DimensionCollection
    {
        return $this->dimensionCollection;
    }

    public function createDimension(
        string $name,
        TranslatableInterface $label,
        mixed $member,
        mixed $rawMember,
        mixed $displayMember,
        bool $interpolation,
    ): DefaultDimension {
        if (\is_object($rawMember)) {
            $signature = hash(
                'xxh128',
                serialize([$name, spl_object_id($rawMember)]),
            );
        } else {
            $signature = hash(
                'xxh128',
                serialize([$name, $rawMember]),
            );
        }

        $dimension = $this->dimensions[$signature] ??= new DefaultDimension(
            name: $name,
            label: $label,
            member: $member,
            rawMember: $rawMember,
            displayMember: $displayMember,
            interpolation: $interpolation,
        );

        if ($interpolation) {
            $this->currentNodesCount++;
            if ($this->currentNodesCount > $this->nodesLimit) {
                throw new InterpolationOverflowException($this->nodesLimit);
            }
        }

        $this->dimensionCollection->collectDimension($dimension);

        return $dimension;
    }
}
