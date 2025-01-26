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

namespace Rekalogika\DoctrineAdvancedGroupBy;

final readonly class Field implements Item
{
    public function __construct(
        private string $name,
    ) {}

    #[\Override]
    public function count(): int
    {
        return 1;
    }

    #[\Override]
    public function getSignature(): string
    {
        return hash('xxh128', self::class . $this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
