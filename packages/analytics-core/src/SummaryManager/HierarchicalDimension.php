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
 * @implements \IteratorAggregate<string,TranslatableInterface>
 */
final readonly class HierarchicalDimension implements
    TranslatableInterface,
    \IteratorAggregate
{
    /**
     * @param array<string,TranslatableInterface> $children
     */
    public function __construct(
        private TranslatableInterface $label,
        private array $children = [],
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->label->trans($translator, $locale);
    }

    /**
     * @return array<string,TranslatableInterface>
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
