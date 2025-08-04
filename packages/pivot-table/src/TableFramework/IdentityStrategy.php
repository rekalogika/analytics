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

namespace Rekalogika\PivotTable\TableFramework;

interface IdentityStrategy
{
    public function getMemberSignature(mixed $member): string;

    /**
     * @param array<string,mixed> $members
     * @return string
     */
    public function getMembersSignature(array $members): string;
}
