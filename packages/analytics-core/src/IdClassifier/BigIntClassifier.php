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

namespace Rekalogika\Analytics\IdClassifier;

use Rekalogika\Analytics\IdClassifier;
use Rekalogika\Analytics\SummaryManager\Query\QueryContext;
use Rekalogika\Analytics\ValueResolver;

final readonly class BigIntClassifier implements IdClassifier
{
    #[\Override]
    public function getDQL(
        ValueResolver $input,
        int $level,
        QueryContext $context,
    ): string {
        return \sprintf(
            'REKALOGIKA_TRUNCATE_BIGINT(%s, %s)',
            $input->getDQL($context),
            $level,
        );
    }
}
