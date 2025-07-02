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

namespace Rekalogika\Analytics\Frontend\Formatter\Property;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\NumberifierAware;
use Rekalogika\Analytics\Frontend\Formatter\Unsupported;
use Rekalogika\Analytics\PivotTable\Model\Property;

final readonly class PropertyNumberifier implements Numberifier, NumberifierAware
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

    private function getNumberifier(): Numberifier
    {
        return $this->numberifier ?? throw new LogicException('Numberifier is not set.');
    }

    #[\Override]
    public function toNumber(mixed $input): float
    {
        if (!$input instanceof Property) {
            throw new Unsupported();
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        return $this->getNumberifier()->toNumber($content);
    }
}
