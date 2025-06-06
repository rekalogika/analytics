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

namespace Rekalogika\Analytics\AggregateFunction;

use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\Context;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;

final readonly class CountDistinct implements AggregateFunction
{
    private ValueResolver $property;

    /**
     * @param 'boolean'|'smallint'|'integer'|'bigint'|'bytea'|'text'|'any' $hashType
     */
    public function __construct(
        string|ValueResolver $property,
        private string $hashType = 'any',
    ) {
        if (\is_string($property)) {
            $property = new PropertyValueResolver($property);
        }

        $this->property = $property;
    }

    #[\Override]
    public function getSourceToSummaryDQLFunction(Context $context): string
    {
        return \sprintf(
            "REKALOGIKA_HLL_ADD_AGG(REKALOGIKA_HLL_HASH(%s, '%s'))",
            $this->property->getDQL($context),
            $this->hashType,
        );
    }

    #[\Override]
    public function getSummaryToSummaryDQLFunction(): string
    {
        return 'REKALOGIKA_HLL_UNION_AGG(%s)';
    }

    #[\Override]
    public function getSummaryReaderDQLFunction(): string
    {
        return 'REKALOGIKA_HLL_CARDINALITY(%s)';
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property->getInvolvedProperties();
    }
}
