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

namespace Rekalogika\PivotTable\TableToCubeAdapter;

use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterDimension;

interface IdentityStrategy
{
    /**
     * @param array<string,TableToCubeAdapterDimension> $tuple
     * @return string
     */
    public function getTupleSignature(array $tuple): string;

    public function getMemberSignature(mixed $member): string;
}
