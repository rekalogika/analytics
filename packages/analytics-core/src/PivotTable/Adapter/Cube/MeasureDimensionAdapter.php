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

namespace Rekalogika\Analytics\PivotTable\Adapter\Cube;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\PivotTable\Util\PropertyMap;
use Rekalogika\PivotTable\Contracts\Cube\Dimension as PivotTableDimension;

final readonly class MeasureDimensionAdapter implements PivotTableDimension
{
    public function __construct(
        private Dimension $dimension,
        private PropertyMap $propertyMap,
    ) {
        if ($dimension->getName() !== '@values') {
            throw new LogicException(
                'Expected a MeasureMember for the "@values" dimension, but got: ' . get_debug_type($dimension->getMember()),
            );
        }
    }

    #[\Override]
    public function getName(): string
    {
        return '@values';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->propertyMap->getDimensionLabel($this->dimension);
    }

    #[\Override]
    public function getMember(): mixed
    {
        /** @psalm-suppress MixedAssignment */
        $member = $this->dimension->getMember();

        if (!$member instanceof MeasureMember) {
            throw new LogicException(
                'Expected a MeasureMember for the "@values" dimension, but got: ' . get_debug_type($this->dimension->getMember()),
            );
        }

        return new MeasureMemberAdapter(
            measureMember: $member,
        );
    }
}
