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

final readonly class PathElement implements \Stringable
{
    /**
     * @param class-string|null $classCast
     */
    private function __construct(
        private string $name,
        private ?string $classCast = null,
    ) {}

    public static function createFromString(string $part): self|Alias
    {
        $part = trim($part);

        if ($part === '*') {
            // Special case for alias, return Alias instance.
            return new Alias();
        }

        // Check if the part contains a class cast, e.g. `property(className)`

        if (str_contains($part, '(')) {
            $parts = explode('(', $part);
            $name = trim($parts[0]);
            $class = rtrim($parts[1], ')');
            $class = trim($class);

            if (!class_exists($class)) {
                throw new LogicException(\sprintf(
                    'Class "%s" not found',
                    $class,
                ));
            }

            return new self(
                name: $name,
                classCast: $class,
            );
        }

        // without class cast.

        return new self(
            name: $part,
            classCast: null,
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%s%s',
            $this->name,
            $this->classCast !== null ? '(' . $this->classCast . ')' : '',
        );
    }


    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return class-string|null
     */
    public function getClassCast(): ?string
    {
        return $this->classCast;
    }
}
