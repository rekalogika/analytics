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

/**
 * @internal
 * @todo deprecate, split into one for each of measure, dimension & values
 */
final readonly class ResultValue
{
    public function __construct(
        private TranslatableInterface|string $label,
        private string $field,
        private mixed $value,
        private mixed $rawValue,
        private int|float $numericValue,
    ) {}

    public function isSame(self $other): bool
    {
        return $this->field === $other->field
            && $this->rawValue === $other->rawValue;
    }

    public function getLabel(): TranslatableInterface|string
    {
        return $this->label;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getRawValue(): mixed
    {
        return $this->rawValue;
    }

    public function getNumericValue(): int|float
    {
        return $this->numericValue;
    }
}
