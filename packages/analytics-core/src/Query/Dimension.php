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

namespace Rekalogika\Analytics\Query;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Represent a dimension
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Dimension
{
    public function getLabel(): string|TranslatableInterface;

    public function getKey(): string;

    public function getValue(): mixed;

    public function getRawValue(): mixed;
}
