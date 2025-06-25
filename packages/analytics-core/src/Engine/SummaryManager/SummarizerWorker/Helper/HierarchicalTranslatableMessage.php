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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class HierarchicalTranslatableMessage implements TranslatableInterface
{
    /**
     * @param list<TranslatableInterface> $labels
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

        return implode(' - ', $strings);
    }
}
