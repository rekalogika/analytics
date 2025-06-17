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

namespace Rekalogika\Analytics\Engine\Util;

use Rekalogika\Analytics\Core\Exception\RuntimeException;

final readonly class DateTimeUtil
{
    private function __construct() {}

    public static function floatToDateTime(float $input): \DateTimeInterface
    {
        $result = \DateTimeImmutable::createFromFormat(
            'U.u',
            number_format($input, 6, '.', ''),
        );

        if (false === $result) {
            throw new RuntimeException(\sprintf(
                'Failed to create DateTimeImmutable from %s.',
                (string) $input,
            ));
        }

        return $result;
    }
}
