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

namespace Rekalogika\Analytics\Contracts\Summary;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;

/**
 * Classifies the partitioning key of the entity into the corresponding
 * partition key in the summary table
 */
interface PartitionKeyClassifier
{
    public function getExpression(
        PartitionValueResolver $input,
        int $level,
        SourceQueryContext $context,
    ): string;
}
