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

namespace Rekalogika\Analytics\Contracts\Translation;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A message to be translated, with its parameters and translation domain. The
 * framework uses it for its own messages, which live in the
 * `rekalogika_analytics` domain by default.
 *
 * Modified from Symfony\Component\Translation\TranslatableMessage, so we don't
 * have to depend on the Translation component.
 *
 * @author Nate Wiebe <nate@northern.co>
 */
final readonly class TranslatableMessage implements TranslatableInterface, \Stringable
{
    /**
     * @param string $message The message id.
     * @param array<string,mixed> $parameters The parameters to substitute in
     * the message. A parameter that is itself translatable is translated
     * first.
     * @param string|null $domain The translation domain.
     */
    public function __construct(
        private string $message,
        private array $parameters = [],
        private ?string $domain = 'rekalogika_analytics',
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return $this->message;
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->message, array_map(
            static fn($parameter): mixed => $parameter instanceof TranslatableInterface ? $parameter->trans($translator, $locale) : $parameter,
            $this->parameters,
        ), $this->domain, $locale);
    }
}
