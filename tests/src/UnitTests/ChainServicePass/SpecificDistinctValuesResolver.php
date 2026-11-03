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

namespace Rekalogika\Analytics\Tests\UnitTests\ChainServicePass;

use Rekalogika\Analytics\Bundle\Common\ApplicableDimension;
use Rekalogika\Analytics\Bundle\Common\ApplicableDimensionsAware;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;

final class SpecificDistinctValuesResolver implements
    DistinctValuesResolver,
    ApplicableDimensionsAware
{
    #[\Override]
    public static function getApplicableDimensions(): iterable
    {
        yield new ApplicableDimension(\stdClass::class, 'specific');
    }

    #[\Override]
    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): iterable {
        return ['specific' => 'resolved by specific'];
    }
}
