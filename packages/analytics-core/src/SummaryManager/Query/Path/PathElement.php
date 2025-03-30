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

use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Exception\LogicException;

final readonly class PathElement
{
    private string $name;

    private bool $isAlias;

    /**
     * @var class-string|null
     */
    private ?string $castToClass;

    public function __construct(string $part)
    {
        if (str_contains($part, '(')) {
            if (str_starts_with($part, '*')) {
                throw new InvalidArgumentException(\sprintf(
                    'Cannot cast to class with alias, omit the * prefix to fix it, part: "%s".',
                    $part,
                ));
            }

            $parts = explode('(', $part);
            $this->name = $parts[0];
            $class = substr($parts[1], 0, -1);

            if (!class_exists($class)) {
                throw new LogicException(\sprintf(
                    'Class "%s" not found',
                    $class,
                ));
            }

            $this->castToClass = $class;
            $this->isAlias = false;

            return;
        }

        $this->isAlias = str_starts_with($part, '*');
        $this->name = $this->isAlias ? substr($part, 1) : $part;
        $this->castToClass = null;
    }

    public function getName(): string
    {
        if ($this->castToClass !== null) {
            return $this->name . '(' . $this->castToClass . ')';
        }

        return $this->name;
    }

    public function getNameWithoutCast(): string
    {
        return $this->name;
    }

    public function isAlias(): bool
    {
        return $this->isAlias;
    }

    /**
     * @return class-string|null
     */
    public function getCastToClass(): ?string
    {
        return $this->castToClass;
    }
}
