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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Query\Result;
use Rekalogika\Analytics\Query\Table;
use Rekalogika\Analytics\Query\TreeResult;

/**
 * @internal
 */
final readonly class DefaultResult implements Result
{
    public function __construct(
        private readonly DefaultTreeResult $treeResult,
        private readonly DefaultTable $table,
    ) {}

    #[\Override]
    public function getTree(): TreeResult
    {
        return $this->treeResult;
    }

    #[\Override]
    public function getTable(): Table
    {
        return $this->table;
    }
}
