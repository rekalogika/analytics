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

namespace Rekalogika\Analytics\Uuid\ValueResolver;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

final readonly class UuidToDateTime implements ValueResolver
{
    public function __construct(
        private string $property,
    ) {}

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [$this->property];
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        return \sprintf(
            'REKALOGIKA_UUID_TO_DATETIME(%s)',
            $context->resolve($this->property),
        );
    }
}
