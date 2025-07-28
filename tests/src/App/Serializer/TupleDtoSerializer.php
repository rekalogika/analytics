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

namespace Rekalogika\Analytics\Tests\App\Serializer;

use Rekalogika\Analytics\Contracts\Serialization\TupleDto;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class TupleDtoSerializer
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    public function serialize(TupleDto $tuple): string
    {
        return $this->serializer->serialize($tuple, 'json');
    }

    public function deserialize(string $data): TupleDto
    {
        return $this->serializer->deserialize($data, TupleDto::class, 'json');
    }
}
