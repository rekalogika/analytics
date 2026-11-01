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
 * Represents a member of a sequence, which is a series of ordered elements. A
 * member knows which members come immediately before and after it, e.g. a
 * month knows the previous and next months.
 *
 * When the members of a dimension are sequence members, the framework can
 * sort them, and fill the gaps in a query result with the members missing
 * between the ones present, so a chart or a pivot table has a continuous axis.
 */
interface SequenceMember extends Comparable
{
    /**
     * Returns the member that comes immediately after this one, or null if
     * this is the last member of the sequence.
     */
    public function getNext(): ?static;

    /**
     * Returns the member that comes immediately before this one, or null if
     * this is the first member of the sequence.
     */
    public function getPrevious(): ?static;

    /**
     * Gets the sequence that this member belongs to. Returns null if the
     * sequence is not known in advance, or the sequence is continuous.
     *
     * A recurring bin, e.g. an hour of the day, has a fixed, bounded sequence
     * (0 to 23), and returns it here. This lets the framework fill a result
     * with every member of the sequence, not only the ones between the
     * members present. A monotonic bin, e.g. a specific month, has no bounds,
     * and returns null.
     *
     * @return Sequence<static>|null
     */
    public function getSequence(): ?Sequence;
}
