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

final readonly class ChartArea
{
    public function __construct(
        private string $baseColor,
        private string $areaTransparency = '60',
        private int $borderWidth = 1,
    ) {}

    /**
     * @return array<string,string|int>
     */
    public function toArray(): array
    {
        return [
            'backgroundColor' => $this->getAreaColor(),
            'borderColor' => $this->getBorderColor(),
            'borderWidth' => $this->getBorderWidth(),
        ];
    }

    public function getAreaColor(): string
    {
        return $this->baseColor . $this->areaTransparency;
    }

    public function getBorderColor(): string
    {
        return $this->baseColor;
    }

    public function getBorderWidth(): int
    {
        return $this->borderWidth;
    }
}
