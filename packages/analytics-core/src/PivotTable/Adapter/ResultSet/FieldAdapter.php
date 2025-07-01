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

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\PivotTable\Model\ResultSet\ResultSetLabel;
use Rekalogika\Analytics\PivotTable\Model\ResultSet\ResultSetMember;
use Rekalogika\PivotTable\Contracts\Result\Field;

final readonly class FieldAdapter implements Field
{
    public function __construct(
        private Dimension $dimension,
    ) {}

    #[\Override]
    public function getKey(): string
    {
        return $this->dimension->getName();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return new ResultSetLabel($this->dimension->getLabel());
    }

    #[\Override]
    public function getItem(): mixed
    {
        return new ResultSetMember($this->dimension->getMember());
    }
}
