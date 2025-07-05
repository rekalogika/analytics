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

namespace Rekalogika\Analytics\Frontend\Formatter\Twig;

use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class StringifierRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private Stringifier $stringifier,
    ) {}

    public function toString(mixed $value): string
    {
        return $this->stringifier->toString($value);
    }
}
