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
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;

final class SpecificValueSerializer implements
    ValueSerializer,
    ApplicableDimensionsAware
{
    #[\Override]
    public static function getApplicableDimensions(): iterable
    {
        yield new ApplicableDimension(\stdClass::class, 'specific');
    }

    #[\Override]
    public function serialize(
        string $class,
        string $dimension,
        mixed $value,
    ): string {
        return 'serialized by specific';
    }

    #[\Override]
    public function deserialize(
        string $class,
        string $dimension,
        int|string|null $identifier,
    ): string {
        return 'deserialized by specific';
    }
}
