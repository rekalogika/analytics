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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Rekalogika\Analytics\Contracts\Result\Unit;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DefaultUnit implements Unit
{
    private function __construct(
        private TranslatableInterface $label,
        private string $signature,
    ) {}

    public static function create(
        null|TranslatableInterface $label,
        null|string $signature,
    ): ?self {
        if ($label === null || $signature === null) {
            return null;
        }

        return new self(
            label: $label,
            signature: $signature,
        );
    }

    #[\Override]
    public function getSignature(): string
    {
        return $this->signature;
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->label->trans($translator, $locale);
    }
}
