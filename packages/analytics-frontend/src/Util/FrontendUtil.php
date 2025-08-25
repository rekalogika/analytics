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

namespace Rekalogika\Analytics\Frontend\Util;

final readonly class FrontendUtil
{
    private function __construct() {}

    /**
     * @param list<string> $dimensions
     * @param list<string> $columns
     * @return list<string>
     */
    public static function getRows(
        array $dimensions,
        array $columns,
    ): array {
        return array_values(array_diff($dimensions, $columns));
    }
}
