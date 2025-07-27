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

namespace Rekalogika\Analytics\Bundle\Common;

final readonly class ApplicableDimension
{
    /**
     * @param class-string $class The summary entity class name.
     * @param string $dimension The dimension name.
     */
    public function __construct(
        private string $class,
        private string $dimension,
    ) {}

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getDimension(): string
    {
        return $this->dimension;
    }
}
