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
 * Represent a field (a dimension or measure) in a summary table
 */
final readonly class Field implements \Stringable, TranslatableInterface
{
    public function __construct(
        private string $key,
        private string|TranslatableInterface $label,
        private string|TranslatableInterface|null $subLabel,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        if ($this->label instanceof \Stringable) {
            $string = (string) $this->label;
        } else {
            return $this->key;
        }

        if ($this->subLabel !== null) {
            if ($this->subLabel instanceof \Stringable) {
                $string .= ' - ' . (string) $this->subLabel;
            } else {
                $string .= ' - (unknown)';
            }
        }

        return $string;
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        if ($this->label instanceof TranslatableInterface) {
            $result = $this->label->trans($translator, $locale);
        } else {
            $result = $this->label;
        }

        if ($this->subLabel !== null) {
            if ($this->subLabel instanceof TranslatableInterface) {
                $result .= ' - ' . $this->subLabel->trans($translator, $locale);
            } else {
                $result .= ' - ' . $this->subLabel;
            }
        }

        return $result;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string|TranslatableInterface
    {
        if ($this->label instanceof \Stringable) {
            return (string) $this->label;
        }

        return $this->label;
    }

    public function getSubLabel(): string|TranslatableInterface|null
    {
        if ($this->subLabel instanceof \Stringable) {
            return (string) $this->subLabel;
        }

        return $this->subLabel;
    }
}
