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

use Rekalogika\Analytics\ValueResolver;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Partition
{
    /**
     * @param null|string|ValueResolver|array<class-string,string|ValueResolver> $source
     */
    public function __construct(
        private null|string|ValueResolver|array $source = null,
    ) {}

    /**
     * @return null|string|ValueResolver|array<class-string,string|ValueResolver>
     */
    public function getSource(): null|string|ValueResolver|array
    {
        return $this->source;
    }
}
