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
use Rekalogika\Analytics\SummaryManager\Query\SummarizerQuery;

/**
 * @internal
 */
final readonly class DefaultResult implements Result
{
    /**
     * @param class-string $summaryClass
     * @param SummarizerQuery $query
     */
    public function __construct(
        private string $summaryClass,
        private SummarizerQuery $query,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getTree(): TreeResult
    {
        return $this->query->getTree();
    }

    #[\Override]
    public function getTable(): Table
    {
        return $this->query->getTable();
    }

    #[\Override]
    public function getNormalTable(): NormalTable
    {
        return $this->query->getNormalTable();
    }
}
