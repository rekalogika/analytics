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

use Rekalogika\Analytics\Bundle\Formatter\BackendNumberifier;
use Rekalogika\Analytics\Bundle\Formatter\Numberifier;
use Rekalogika\Analytics\Bundle\Formatter\NumberifierAware;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeWrapper;

/**
 * @deprecated
 */
final readonly class NodeWrapperNumberifier implements BackendNumberifier, NumberifierAware
{
    public function __construct(
        private ?Numberifier $numberifier = null,
    ) {}

    #[\Override]
    public function withNumberifier(Numberifier $numberifier): static
    {
        if ($this->numberifier === $numberifier) {
            return $this;
        }

        return new static($numberifier);
    }

    #[\Override]
    public function toNumber(mixed $input): null|int|float
    {
        if (!$input instanceof NodeWrapper) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        if (\is_int($content) || \is_float($content)) {
            return $content;
        }

        if ($this->numberifier === null) {
            throw new \LogicException('Numberifier is not set.');
        }

        return $this->numberifier->toNumber($content);
    }
}
