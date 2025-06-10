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

namespace Rekalogika\Analytics\PartitionKeyClassifier;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\PartitionKeyClassifier;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;

final readonly class BigIntClassifier implements PartitionKeyClassifier
{
    #[\Override]
    public function getExpression(
        PartitionValueResolver $input,
        int $level,
        SourceQueryContext $context,
    ): string {
        return \sprintf(
            'REKALOGIKA_TRUNCATE_BIGINT(%s, %s)',
            $input->getExpression($context),
            $level,
        );
    }
}
