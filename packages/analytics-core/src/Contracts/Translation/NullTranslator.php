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

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Translator that returns the message as is, only substituting the
 * parameters. Used where a translator is required but none is available, e.g.
 * to build the default message of translatable exceptions.
 */
final class NullTranslator implements TranslatorInterface, LocaleAwareInterface
{
    use TranslatorTrait;
}
