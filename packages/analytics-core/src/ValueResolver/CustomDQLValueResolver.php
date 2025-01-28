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

namespace Rekalogika\Analytics\ValueResolver;

use Rekalogika\Analytics\SummaryManager\Query\QueryContext;
use Rekalogika\Analytics\ValueResolver;

final readonly class CustomDQLValueResolver implements ValueResolver
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        private string $dql,
        private array $fields = [],
    ) {}

    /**
     * Returns only the fields directly on the related entity
     */
    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->fields;
    }

    #[\Override]
    public function getDQL(QueryContext $context): string
    {
        $resolvedParameters = array_map(
            fn($parameter): string => $context->resolvePath($parameter),
            $this->fields,
        );

        return \sprintf($this->dql, ...$resolvedParameters);
    }
}
