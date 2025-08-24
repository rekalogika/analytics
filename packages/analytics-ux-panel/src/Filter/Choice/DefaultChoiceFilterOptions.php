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

/**
 * @template T
 * @implements ChoiceFilterOptions<T>
 */
final readonly class DefaultChoiceFilterOptions implements ChoiceFilterOptions
{
    /**
     * @param array<string,T> $choices
     */
    public function __construct(
        private array $choices,
    ) {}

    #[\Override]
    public function getChoices(): iterable
    {
        foreach ($this->choices as $id => $value) {
            /** @psalm-suppress RedundantCastGivenDocblockType */
            $id = (string) $id; // Ensure id is a string.
            yield $id => $value;
        }
    }

    #[\Override]
    public function getValueFromId(string $id): mixed
    {
        return $this->choices[$id] ?? null;
    }
}
