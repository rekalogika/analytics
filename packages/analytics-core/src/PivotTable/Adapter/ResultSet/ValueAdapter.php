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

namespace Rekalogika\Analytics\PivotTable\Adapter\ResultSet;

use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\PivotTable\Model\ResultSet\ResultSetLabel;
use Rekalogika\Analytics\PivotTable\Model\ResultSet\ResultSetValue;
use Rekalogika\PivotTable\Contracts\Result\Value;

final readonly class ValueAdapter implements Value
{
    public function __construct(
        private Measure $measure,
    ) {}

    #[\Override]
    public function getKey(): string
    {
        return $this->measure->getName();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return new ResultSetLabel($this->measure->getLabel());
    }

    #[\Override]
    public function getValue(): mixed
    {
        return new ResultSetValue($this->measure->getValue());
    }
}
