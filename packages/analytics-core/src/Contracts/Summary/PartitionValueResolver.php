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

interface PartitionValueResolver extends ValueResolver
{
    /**
     * The property of the source entity that is used as the source value.
     * Unlike ValueResolver, here we return one and only one value.
     *
     * @return list{string}
     */
    #[\Override]
    public function getInvolvedProperties(): array;

    /**
     * Transforms a source value to the summary value in PHP.
     */
    public function transformSourceValueToSummaryValue(mixed $value): int;

    /**
     * Transforms a summary value to the source value in PHP.
     */
    public function transformSummaryValueToSourceValue(int $value): mixed;
}
