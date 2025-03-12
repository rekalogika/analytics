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
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultValue;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultMeasure implements Measure
{
    public function __construct(
        private string|TranslatableInterface $label,
        private string $key,
        private mixed $value,
        private mixed $rawValue,
        private int|float $numericValue,
        private ?Unit $unit,
    ) {}

    public static function createFromResultValue(ResultValue $resultValue): self
    {
        $unit = DefaultUnit::createFromResultValue($resultValue);

        return new self(
            label: $resultValue->getLabel(),
            key: $resultValue->getField(),
            value: $resultValue->getValue(),
            rawValue: $resultValue->getRawValue(),
            numericValue: $resultValue->getNumericValue(),
            unit: $unit,
        );
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
