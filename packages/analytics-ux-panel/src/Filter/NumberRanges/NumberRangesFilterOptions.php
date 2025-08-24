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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges;

use Symfony\Contracts\Translation\TranslatableInterface;

interface NumberRangesFilterOptions
{
    public function getLabel(): TranslatableInterface;

    public function getHelp(): ?TranslatableInterface;

    public function transformInputToDatabaseValue(int $number): mixed;
}
