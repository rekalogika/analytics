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

namespace Rekalogika\Analytics\SimpleQueryBuilder\Path;

use Rekalogika\Analytics\Exception\LogicException;

final readonly class Path implements \Stringable, \Countable
{
    /**
     * @param non-empty-list<PathElement|Alias> $elements
     */
    private function __construct(
        private array $elements,
    ) {}

    #[\Override]
    public function count(): int
    {
        return \count($this->elements);
    }

    #[\Override]
    public function __toString(): string
    {
        return implode('.', array_map(
            fn(PathElement|Alias $element): string => (string) $element,
            $this->elements,
        ));
    }

    public static function createFromString(string $path): self
    {
        $elements = array_map(
            fn(string $part): PathElement|Alias => PathElement::createFromString(trim($part)),
            explode('.', $path),
        );

        return new self($elements);
    }

    public function getFirstElement(): PathElement|Alias
    {
        return $this->elements[0]
            ?? throw new LogicException('Path is empty, cannot get first element');
    }

    /**
     * @return list{PathElement|Alias,self}
     */
    public function shift(): array
    {
        $elements = $this->elements;
        $firstElement = array_shift($elements);

        if ($elements === []) {
            throw new LogicException('Shifting to an empty path is not allowed');
        }

        return [$firstElement, new self($elements)];
    }
}
