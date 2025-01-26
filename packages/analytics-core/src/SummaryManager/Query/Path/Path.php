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

namespace Rekalogika\Analytics\SummaryManager\Query\Path;

final class Path implements \Countable
{
    /**
     * @var list<PathElement>
     */
    private array $previousPath = [];

    /**
     * @var list<PathElement>
     */
    private array $path;

    public function __construct(string $path)
    {
        $this->path = array_map(
            fn(string $part): PathElement => new PathElement($part),
            explode('.', $path),
        );
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->path);
    }

    public function getFirstPart(): PathElement
    {
        return $this->path[0] ?? throw new \RuntimeException('Path is empty');
    }

    public function getPreviousFullPath(): string
    {
        return implode('.', array_map(
            fn(PathElement $part): string => $part->getName(),
            $this->previousPath,
        ));
    }

    public function getFullPathToFirst(bool $withCast = true): string
    {
        return implode('.', array_map(
            fn(PathElement $part): string => $withCast ? $part->getName() : $part->getNameWithoutCast(),
            [...$this->previousPath, $this->getFirstPart()],
        ));
    }

    public function toString(): string
    {
        return implode('.', array_map(
            fn(PathElement $part): string => $part->getName(),
            $this->path,
        ));
    }

    public function shift(): void
    {
        $part = array_shift($this->path);

        if ($part === null) {
            throw new \RuntimeException('Path is empty');
        }

        $this->previousPath[] = $part;
    }


}
