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
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Formatter\StringifierAware;
use Rekalogika\Analytics\Frontend\Formatter\Unsupported;
use Rekalogika\Analytics\PivotTable\Model\Property;

final readonly class PropertyStringifier implements Stringifier, StringifierAware
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

    private function getStringifier(): Stringifier
    {
        return $this->stringifier ?? throw new LogicException('Stringifier is not set.');
    }

    #[\Override]
    public function toString(mixed $input): string
    {
        if (!$input instanceof Property) {
            throw new Unsupported();
        }

        /** @psalm-suppress MixedAssignment */
        $content = $input->getContent();

        return $this->getStringifier()->toString($content);
    }
}
