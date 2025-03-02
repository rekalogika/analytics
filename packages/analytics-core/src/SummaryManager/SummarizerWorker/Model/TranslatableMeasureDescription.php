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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class TranslatableMeasureDescription implements
    MeasureDescription,
    TranslatableInterface,
    \Stringable
{
    public function __construct(
        private string $measurePropertyName,
        private TranslatableInterface $label,
    ) {}

    #[\Override]
    public function getMeasurePropertyName(): string
    {
        return $this->measurePropertyName;
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label->trans($translator, $locale);
    }

    #[\Override]
    public function __toString(): string
    {
        if ($this->label instanceof \Stringable) {
            return (string) $this->label;
        } else {
            return $this->measurePropertyName;
        }
    }
}
