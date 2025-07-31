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

namespace Rekalogika\Analytics\Contracts\Dto;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

abstract readonly class ExpressionDto
{
    /**
     * @return array<string,mixed>
     */
    abstract public function toArray(): array;

    /**
     * @param array<string,mixed> $array
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $array): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!isset($array['class']) || !\is_string($array['class'])) {
            throw new InvalidArgumentException('Invalid array representation for ExpressionDto.');
        }

        switch ($array['class']) {
            case 'comparison':
                return ComparisonDto::fromArray($array);
            case 'compositeExpression':
                return CompositeExpressionDto::fromArray($array);
            case 'value':
                return ValueDto::fromArray($array);
            default:
                throw new InvalidArgumentException('Unsupported expression class: ' . $array['class']);
        }
    }
}
