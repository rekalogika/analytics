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

namespace Rekalogika\Analytics\Bundle\UI;

/**
 * @template T of Filter
 */
interface SpecificFilterFactory extends FilterFactory
{
    /**
     * @return class-string<T>
     */
    public static function getFilterClass(): string;

    /**
     * @param class-string $summaryClass
     * @param array<string,mixed> $inputArray
     * @return T
     */
    #[\Override]
    public function createFilter(
        string $summaryClass,
        string $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter;
}
