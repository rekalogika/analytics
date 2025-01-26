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

namespace Rekalogika\Analytics\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Hierarchy
{
    /**
     * @param list<list<int>> $paths
     */
    public function __construct(
        private array $paths,
    ) {}

    /**
     * @return list<list<int>>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
