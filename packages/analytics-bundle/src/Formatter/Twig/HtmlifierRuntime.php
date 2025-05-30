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

namespace Rekalogika\Analytics\Bundle\Formatter\Twig;

use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class HtmlifierRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private Htmlifier $htmlifier,
    ) {}

    public function toHtml(mixed $value): string
    {
        return $this->htmlifier->toHtml($value);
    }
}
