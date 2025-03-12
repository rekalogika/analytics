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

namespace Rekalogika\Analytics\Util;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslatablePropertyDimension implements TranslatableInterface
{
    public function __construct(
        private string|TranslatableInterface $propertyLabel,
        private string|TranslatableInterface $dimensionLabel,
    ) {}

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        if ($this->propertyLabel instanceof TranslatableInterface) {
            $translatedProperty = $this->propertyLabel->trans($translator, $locale);
        } else {
            $translatedProperty = $this->propertyLabel;
        }

        if ($this->dimensionLabel instanceof TranslatableInterface) {
            $translatedDimension = $this->dimensionLabel->trans($translator, $locale);
        } else {
            $translatedDimension = $this->dimensionLabel;
        }

        return \sprintf('%s - %s', $translatedProperty, $translatedDimension);
    }
}
