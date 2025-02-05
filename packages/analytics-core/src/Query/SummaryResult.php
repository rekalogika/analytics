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

namespace Rekalogika\Analytics\Query;

interface SummaryResult
{
    /**
     * @return list<SummaryNode>
     */
    public function getChildren(): array;

    public function getChild(mixed $item): ?SummaryNode;

    public function getPath(mixed ...$items): ?SummaryNode;
}
