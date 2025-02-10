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
 */
final readonly class ResultValue
{
    public function __construct(
        private readonly TranslatableInterface|string $label,
        private readonly string $field,
        private readonly mixed $value,
        private readonly mixed $rawValue,
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
}
