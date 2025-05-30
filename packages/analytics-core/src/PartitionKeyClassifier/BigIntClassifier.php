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

use Rekalogika\Analytics\Contracts\Summary\Context;
use Rekalogika\Analytics\Contracts\Summary\PartitionKeyClassifier;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;

final readonly class BigIntClassifier implements PartitionKeyClassifier
{
    #[\Override]
    public function getDQL(
        PartitionValueResolver $input,
        int $level,
        Context $context,
    ): string {
        return \sprintf(
            'REKALOGIKA_TRUNCATE_BIGINT(%s, %s)',
            $input->getDQL($context),
            $level,
        );
    }
}
