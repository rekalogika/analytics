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

namespace Rekalogika\Analytics\Frontend\Chart\Configuration;

use OzdemirBurak\Iris\Color\Hsl;

final class ColorDispenser
{
    private readonly Hsl $hsl;

    private float $hue = 240.0;

    public function __construct()
    {
        $this->hsl = new Hsl('hsl(240,40%,50%)');
    }

    /**
     * @see https://softwareengineering.stackexchange.com/a/303974
     */
    public function dispenseColor(): string
    {
        // not using Hsl::spin(), it applies `%` to the float hue, which
        // truncates it and triggers a deprecation since PHP 8.1
        $this->hue = fmod($this->hue + 137.5, 360.0);
        $this->hsl->hue($this->hue);

        return (string) $this->hsl->toHex();
    }
}
