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

namespace Rekalogika\Analytics\Bundle\UI\Model;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements \IteratorAggregate<Choice>
 */
final class Choices implements TranslatableInterface, \IteratorAggregate
{
    /**
     * @param iterable<Choice> $choices
     */
    public function __construct(
        private TranslatableInterface $label,
        private iterable $choices,
    ) {}

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->label->trans($translator, $locale);
    }

    /**
     * @return \Traversable<Choice>
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->choices;
    }
}
