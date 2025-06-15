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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Result\Measure;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DefaultMeasure implements Measure
{
    public function __construct(
        private TranslatableInterface $label,
        private string $name,
        private mixed $value,
        private mixed $rawValue,
        private ?DefaultUnit $unit,
    ) {}

    public static function createNull(
        TranslatableInterface $label,
        string $name,
        ?DefaultUnit $unit,
    ): self {
        return new self(
            label: $label,
            name: $name,
            value: null,
            rawValue: null,
            unit: $unit,
        );
    }

    public static function createNullFromSelf(DefaultMeasure $measure): self
    {
        return self::createNull(
            label: $measure->label,
            name: $measure->name,
            unit: $measure->unit,
        );
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
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
    public function getUnit(): ?DefaultUnit
    {
        return $this->unit;
    }
}
