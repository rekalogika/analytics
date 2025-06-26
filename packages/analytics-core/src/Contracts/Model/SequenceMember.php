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
 * Represents a sequence in a data set, which is a series of ordered elements.
 */
interface SequenceMember extends Comparable
{
    public function getNext(): ?static;

    public function getPrevious(): ?static;

    /**
     * Gets the sequence that this member belongs to. Returns null if the
     * sequence is not known in advance, or the sequence is continuous.
     *
     * @return Sequence<static>|null
     */
    public function getSequence(): ?Sequence;
}
