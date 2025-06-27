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

namespace Rekalogika\Analytics\PostgreSQLHll;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ApproximateCount implements TranslatableInterface, \Stringable
{
    public function __construct(
        private int $approximateCount,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->approximateCount;
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return \sprintf('~%d', $this->approximateCount);
    }
}
