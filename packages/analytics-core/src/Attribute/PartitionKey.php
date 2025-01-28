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

use Rekalogika\Analytics\PartitionKeyClassifier;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class PartitionKey
{
    public function __construct(
        private PartitionKeyClassifier $classifier,
    ) {}

    public function getClassifier(): PartitionKeyClassifier
    {
        return $this->classifier;
    }
}
