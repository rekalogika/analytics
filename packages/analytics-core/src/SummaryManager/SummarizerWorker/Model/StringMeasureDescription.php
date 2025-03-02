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

final readonly class StringMeasureDescription implements MeasureDescription, \Stringable
{
    public function __construct(
        private string $measurePropertyName,
        private string $label,
    ) {}

    #[\Override]
    public function getMeasurePropertyName(): string
    {
        return $this->measurePropertyName;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->label;
    }
}
