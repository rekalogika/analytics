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

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Choice implements TranslatableInterface
{
    public function __construct(
        private string $id,
        private mixed $value,
        private string|TranslatableInterface $label,
    ) {}

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null
    ): string {
        if ($this->label instanceof TranslatableInterface) {
            return $this->label->trans($translator, $locale);
        }

        return $this->label;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getLabel(): TranslatableInterface|string
    {
        return $this->label;
    }
}
