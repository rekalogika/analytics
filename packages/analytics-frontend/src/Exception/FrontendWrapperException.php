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

namespace Rekalogika\Analytics\Frontend\Exception;

use Rekalogika\Analytics\Common\Exception\RuntimeException;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Common\Util\NullTranslator;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * If the previous exception implements TranslatableInterface, then it is
 * assumed the message is user-friendly and does not contain any technical
 * details.
 */
final class FrontendWrapperException extends RuntimeException implements AnalyticsFrontendException
{
    private TranslatableInterface $translatable;

    /**
     * Wrap user-friendly exception from upstream, otherwise return the previous
     * exception as is.
     */
    public static function selectiveWrap(\Throwable $previous): \Throwable
    {
        if ($previous instanceof TranslatableInterface) {
            return new self($previous);
        }

        return $previous;
    }

    public static function wrap(\Throwable $previous): self
    {
        return new self($previous);
    }

    private function __construct(\Throwable $previous)
    {
        if ($previous instanceof TranslatableInterface) {
            $this->translatable = $previous;
        } else {
            $this->translatable = new TranslatableMessage(
                'An error occurred. Please try again later and contact technical support if the problem persists.',
            );
        }

        parent::__construct($this->trans(new NullTranslator()), 0, $previous);
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->translatable->trans($translator, $locale);
    }
}
