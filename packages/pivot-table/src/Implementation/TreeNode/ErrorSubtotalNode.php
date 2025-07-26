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

namespace Rekalogika\PivotTable\Implementation\TreeNode;

use Rekalogika\PivotTable\Contracts\Tree\SubtotalNode;

final readonly class ErrorSubtotalNode implements SubtotalNode
{
    #[\Override]
    public function getKey(): string
    {
        return 'error';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return 'error';
    }

    #[\Override]
    public function getItem(): mixed
    {
        return 'error';
    }

    #[\Override]
    public function getValue(): mixed
    {
        return 'error';
    }
}
