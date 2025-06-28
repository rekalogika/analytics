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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\Model;

final readonly class Choice
{
    /**
     * Sentinel value to indicate null, used in query strings
     */
    public const NULL = "\x1E";

    public function __construct(
        private string $id,
        private mixed $value,
        private string $label,
        private bool $selected,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }
}
