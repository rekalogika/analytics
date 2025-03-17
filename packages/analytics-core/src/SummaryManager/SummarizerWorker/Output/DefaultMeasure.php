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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Query\Measure;
use Rekalogika\Analytics\Query\Unit;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultMeasure implements Measure
{
    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private string $summaryClass,
        private string|TranslatableInterface $label,
        private string $key,
        private mixed $value,
        private mixed $rawValue,
        private int|float $numericValue,
        private ?Unit $unit,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getLabel(): string|TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->key;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function getRawValue(): mixed
    {
        return $this->rawValue;
    }

    #[\Override]
    public function getNumericValue(): int|float
    {
        return $this->numericValue;
    }

    #[\Override]
    public function getUnit(): ?Unit
    {
        return $this->unit;
    }
}
