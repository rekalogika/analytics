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

namespace Rekalogika\Analytics\Bundle\UI\Formatter;

use Rekalogika\Analytics\Bundle\Formatter\BackendNumberifier;
use Rekalogika\Analytics\Bundle\Formatter\Numberifier;
use Rekalogika\Analytics\Bundle\Formatter\NumberifierAware;
use Rekalogika\Analytics\PivotTable\Model\NodeProperty;

final readonly class NodePropertyNumberifier implements BackendNumberifier, NumberifierAware
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

        return new self($numberifier);
    }

    #[\Override]
    public function toNumber(mixed $input): null|int|float
    {
        if (!$input instanceof NodeProperty) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        return $this->numberifier?->toNumber($content);
    }
}
