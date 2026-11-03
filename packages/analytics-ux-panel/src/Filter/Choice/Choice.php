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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\Choice;

final class Choice
{
    /**
     * Sentinel value to indicate null, used in query strings
     */
    public const NULL = "\x1E";

    /**
     * @param string|\Closure():string $htmlLabel The HTML label, or a closure
     * returning it. The closure is called on the first call to
     * `getHtmlLabel()`, so the potentially expensive conversion is skipped if
     * the HTML label is never rendered.
     */
    public function __construct(
        private readonly string $id,
        private readonly mixed $value,
        private readonly string $label,
        private string|\Closure $htmlLabel,
        private readonly bool $selected,
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

    public function getHtmlLabel(): string
    {
        if ($this->htmlLabel instanceof \Closure) {
            $this->htmlLabel = ($this->htmlLabel)();
        }

        return $this->htmlLabel;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }
}
