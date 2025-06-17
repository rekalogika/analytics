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

namespace Rekalogika\Analytics\Common\Model;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class TranslatablePropertyDimension implements TranslatableInterface
{
    public function __construct(
        private TranslatableInterface $propertyLabel,
        private TranslatableInterface $dimensionLabel,
    ) {}

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $translatedProperty = $this->propertyLabel->trans($translator, $locale);
        $translatedDimension = $this->dimensionLabel->trans($translator, $locale);

        return \sprintf('%s - %s', $translatedProperty, $translatedDimension);
    }
}
