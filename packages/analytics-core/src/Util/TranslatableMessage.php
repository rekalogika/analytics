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

/**
 * Modified from Symfony\Component\Translation\TranslatableMessage, so we don't
 * have to depend on the Translation component.
 *
 * @author Nate Wiebe <nate@northern.co>
 */
final class TranslatableMessage implements TranslatableInterface
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function __construct(
        private string $message,
        private array $parameters = [],
        private ?string $domain = null,
    ) {}

    public function __toString(): string
    {
        return $this->message;
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->message, array_map(
            static fn($parameter) => $parameter instanceof TranslatableInterface ? $parameter->trans($translator, $locale) : $parameter,
            $this->parameters,
        ), $this->domain, $locale);
    }
}
