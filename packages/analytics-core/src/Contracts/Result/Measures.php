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

namespace Rekalogika\Analytics\Contracts\Result;

/**
 * Collection of measures, in no particular order.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends \Traversable<string,Measure>
 */
interface Measures extends \Traversable, \Countable
{
    public function getByName(string $name): ?Measure;

    public function getByIndex(int $index): ?Measure;

    public function has(string $name): bool;
}
