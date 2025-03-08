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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

use Rekalogika\Analytics\Query\Result;
use Rekalogika\Analytics\Query\TreeResult;

final readonly class DefaultResult implements Result
{
    public function __construct(
        private readonly TreeResult $treeResult,
    ) {}

    #[\Override]
    public function getTree(): TreeResult
    {
        return $this->treeResult;
    }
}
