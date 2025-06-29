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

namespace Rekalogika\Analytics\UX\PanelBundle\Internal;

use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PivotTableItemOption implements TranslatableInterface
{
    public function __construct(
        private DimensionMetadata $dimension,
    ) {}

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->getLabel()->trans($translator, $locale);
    }

    public function getKey(): string
    {
        return $this->dimension->getName();
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->dimension->getLabel();
    }

    public function getOptGroupId(): string
    {
        $name = $this->dimension->getName();
        $propertyName = $this->dimension->getPropertyName();

        // remove from $name the last part containing $propertyName
        if (str_ends_with($name, $propertyName)) {
            $name = substr($name, 0, -\strlen($propertyName) + 1);
        }

        return $name;
    }
}
