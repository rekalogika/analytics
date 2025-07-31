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

namespace Rekalogika\Analytics\Serialization\Mapper;

/**
 * @template O of object
 * @template Dto of object
 */
interface Mapper
{
    /**
     * @param O $object
     * @return Dto
     */
    public function toDto(object $object, MapperContext $context): object;

    /**
     * @param Dto $dto
     * @return O
     */
    public function fromDto(object $dto, MapperContext $context): object;
}
