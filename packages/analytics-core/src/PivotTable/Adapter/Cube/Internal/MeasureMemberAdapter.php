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

namespace Rekalogika\Analytics\PivotTable\Adapter\Cube\Internal;

use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\PivotTable\Contracts\Cube\MeasureMember as PivotTableMeasureMember;

final readonly class MeasureMemberAdapter implements PivotTableMeasureMember
{
    public function __construct(
        private MeasureMember $measureMember,
    ) {}

    #[\Override]
    public function getMeasureName(): string
    {
        return $this->measureMember->getMeasureProperty();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->measureMember;
    }

    public function getWrapped(): MeasureMember
    {
        return $this->measureMember;
    }
}
