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
 * A sequence, which is a bounded series of ordered elements. Iterating the
 * sequence yields every member from the first to the last, including the ones
 * in between.
 *
 * @template T of SequenceMember
 * @extends \Traversable<T>
 */
interface Sequence extends \Traversable
{
    /**
     * The first member of the sequence.
     *
     * @return T
     */
    public function getFirst(): SequenceMember;

    /**
     * The last member of the sequence.
     *
     * @return T
     */
    public function getLast(): SequenceMember;
}
