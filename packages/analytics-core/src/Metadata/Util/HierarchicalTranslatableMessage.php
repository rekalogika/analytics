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

namespace Rekalogika\Analytics\Metadata\Util;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translatable that translates each of several labels and joins them with
 * " / ". Used to display the path of a dimension inside dimension groups,
 * e.g. "Time / Civil / Year".
 *
 * @see \Rekalogika\Analytics\Metadata\Summary\HierarchyAwareLabel
 */
final readonly class HierarchicalTranslatableMessage implements TranslatableInterface
{
    /**
     * @param list<TranslatableInterface> $labels The labels to join, in
     * order.
     */
    public function __construct(
        private array $labels,
    ) {}

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $strings = [];

        foreach ($this->labels as $label) {
            $strings[] = $label->trans($translator, $locale);
        }

        return implode(' / ', $strings);
    }
}
