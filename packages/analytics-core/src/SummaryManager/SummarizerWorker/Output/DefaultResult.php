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

use Rekalogika\Analytics\Query\NormalTable;
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
        private readonly DefaultNormalTable $normalTable,
    ) {}

    public static function createEmpty(): self
    {
        return new self(
            treeResult: new DefaultTreeResult([]),
            table: new DefaultTable([]),
            normalTable: new DefaultNormalTable([]),
        );
    }

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

    #[\Override]
    public function getNormalTable(): NormalTable
    {
        return $this->normalTable;
    }
}
