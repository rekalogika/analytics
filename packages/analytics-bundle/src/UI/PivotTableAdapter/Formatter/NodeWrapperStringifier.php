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

namespace Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Formatter;

use Rekalogika\Analytics\Bundle\Formatter\BackendStringifier;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\Formatter\StringifierAware;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeWrapper;

final readonly class NodeWrapperStringifier implements BackendStringifier, StringifierAware
{
    public function __construct(
        private ?Stringifier $stringifier = null,
    ) {}

    #[\Override]
    public function withStringifier(Stringifier $stringifier): static
    {
        if ($this->stringifier === $stringifier) {
            return $this;
        }

        return new self($stringifier);
    }

    #[\Override]
    public function toString(mixed $input): ?string
    {
        if (!$input instanceof NodeWrapper) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        return $this->stringifier?->toString($content);
    }
}
