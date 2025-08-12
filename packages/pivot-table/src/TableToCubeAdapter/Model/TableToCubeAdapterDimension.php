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

namespace Rekalogika\PivotTable\TableToCubeAdapter\Model;

use Rekalogika\PivotTable\Contracts\Cube\Dimension;

final readonly class TableToCubeAdapterDimension implements Dimension
{
    public function __construct(
        private string $name,
        private mixed $legend,
        private mixed $member,
    ) {}

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->legend;
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->member;
    }
}
