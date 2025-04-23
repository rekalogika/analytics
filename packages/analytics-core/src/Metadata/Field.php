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

namespace Rekalogika\Analytics\Metadata;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Represent a field (a dimension or measure) in a summary table
 */
final readonly class Field implements TranslatableInterface
{
    public function __construct(
        private string $key,
        private TranslatableInterface $label,
        private TranslatableInterface|null $subLabel,
    ) {}

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $result = $this->label->trans($translator, $locale);

        if ($this->subLabel !== null) {
            $result .= ' - ' . $this->subLabel->trans($translator, $locale);
        }

        return $result;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    public function getSubLabel(): TranslatableInterface|null
    {
        return $this->subLabel;
    }
}
