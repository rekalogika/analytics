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
 * A sequence that has a bounded range, meaning it has a defined start and end.
 */
interface BoundedSequence extends Sequence
{
    public function getFirst(): static;

    public function getLast(): static;
}
