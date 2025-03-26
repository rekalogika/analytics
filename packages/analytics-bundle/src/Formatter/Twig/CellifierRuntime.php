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

use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\Bundle\Formatter\CellProperties;
use Twig\Extension\RuntimeExtensionInterface;

final class CellifierRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private Cellifier $cellifier,
    ) {}

    public function toCell(mixed $value): CellProperties
    {
        return $this->cellifier->toCell($value);
    }
}
