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

namespace Rekalogika\Analytics\Engine\Sequence;

use Rekalogika\Analytics\Contracts\Model\Sequence;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;

final readonly class SequenceUtil
{
    private function __construct() {}

    /**
     * Creates a sequence from the provided first and last members. If the
     * sequence's first and last members are known, then we are using that
     * instead.
     *
     * Example: If 2025-03-06 and 2025-03-09 are the first and last members,
     * then it returns a sequence of 2025-03-06, 2025-03-07, 2025-03-08, and
     * 2025-03-09. If March and May are the first and last members, then it
     * returns a sequence from January to December.
     *
     * @template T of SequenceMember
     * @param T $first
     * @param T $last
     * @return Sequence<T>
     */
    public static function getSequenceFromMembers(
        SequenceMember $first,
        SequenceMember $last,
    ): Sequence {
        $sequence = $first->getSequence();

        if ($sequence !== null) {
            return $sequence;
        }

        return new DefaultSequence($first, $last);
    }
}
