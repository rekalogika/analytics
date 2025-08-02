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

namespace Rekalogika\Analytics\Bundle\EventListener;

use Rekalogika\Analytics\Engine\Entity\PartitionRange;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;

final readonly class Printer
{
    private function __construct() {}

    public static function print(mixed $value): string
    {
        if (\is_scalar($value)) {
            return (string) $value;
        } elseif ($value instanceof \Stringable) {
            return $value->__toString();
        } elseif ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        } elseif ($value instanceof \DateInterval) {
            return (string) (
                $value->days * 86400
                + $value->h * 3600
                + $value->i * 60
                + $value->s
                + (int) $value->f
            );
        } elseif ($value instanceof PartitionRange) {
            return PartitionUtil::printRange($value);
        } else {
            return get_debug_type($value);
        }
    }
}
