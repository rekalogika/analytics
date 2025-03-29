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

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Represent a property, which can be either a dimension or a measure
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Property
{
    /**
     * Property name (e.g. country, time.hour, count, etc)
     */
    public function getKey(): string;

    /**
     * Description of the property (e.g. Country, Hour of the day)
     */
    public function getLabel(): TranslatableInterface;
}
