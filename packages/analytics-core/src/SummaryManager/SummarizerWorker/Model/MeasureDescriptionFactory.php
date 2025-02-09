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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

use Symfony\Contracts\Translation\TranslatableInterface;

final class MeasureDescriptionFactory
{
    /**
     * @var array<string,MeasureDescription>
     */
    private array $cache = [];

    public function createMeasureDescription(
        string $measurePropertyName,
        string|TranslatableInterface $label,
    ): MeasureDescription {
        $cacheKey = $this->getCacheKey($measurePropertyName, $label);

        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        if ($label instanceof TranslatableInterface) {
            return $this->cache[$cacheKey] = new TranslatableMeasureDescription(
                measurePropertyName: $measurePropertyName,
                label: $label,
            );
        } else {
            return $this->cache[$cacheKey] = new StringMeasureDescription(
                measurePropertyName: $measurePropertyName,
                label: $label,
            );
        }
    }

    private function getCacheKey(
        string $measurePropertyName,
        string|TranslatableInterface $label,
    ): string {
        return hash('xxh128', serialize([$measurePropertyName, $label]));
    }
}
