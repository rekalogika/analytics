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

namespace Rekalogika\Analytics\Contracts\Model;

/**
 * Represents a grouping column in a summary query. A selected field means the
 * field will be a non-grouping field.
 */
interface GroupingFields
{
    /**
     * @return list<string>
     */
    public function getAvailableFields(): array;

    public function selectField(
        string $identifier,
    ): void;
}
