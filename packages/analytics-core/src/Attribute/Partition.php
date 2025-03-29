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

use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Partition
{
    /**
     * @param null|string|PartitionValueResolver|array<class-string,string|PartitionValueResolver> $source
     */
    public function __construct(
        private null|string|PartitionValueResolver|array $source = null,
    ) {}

    /**
     * @return null|string|PartitionValueResolver|array<class-string,string|PartitionValueResolver>
     */
    public function getSource(): null|string|PartitionValueResolver|array
    {
        return $this->source;
    }
}
