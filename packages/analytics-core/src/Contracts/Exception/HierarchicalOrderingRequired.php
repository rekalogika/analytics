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

namespace Rekalogika\Analytics\Contracts\Exception;

final class HierarchicalOrderingRequired extends RuntimeException
{
    public function __construct(
        string $message = 'Hierarchical ordering is required for the requested result type. Without hierarchical ordering, only the table result is available.',
    ) {
        parent::__construct($message);
    }
}
