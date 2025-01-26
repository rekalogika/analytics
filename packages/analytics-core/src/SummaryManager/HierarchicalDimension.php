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

namespace Rekalogika\Analytics\SummaryManager;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements \IteratorAggregate<string,string|TranslatableInterface>
 */
final readonly class HierarchicalDimension implements
    \Stringable,
    TranslatableInterface,
    \IteratorAggregate
{
    /**
     * @param array<string,string|TranslatableInterface> $children
     */
    public function __construct(
        private string|TranslatableInterface $label,
        private array $children = [],
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    #[\Override]
    public function __toString(): string
    {
        if (\is_string($this->label)) {
            return $this->label;
        }

        if ($this->label instanceof \Stringable) {
            return $this->label->__toString();
        }

        return '(unknown)';
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        if ($this->label instanceof TranslatableInterface) {
            return $this->label->trans($translator, $locale);
        }

        return $this->label;
    }

    /**
     * @return array<string,string|TranslatableInterface>
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
