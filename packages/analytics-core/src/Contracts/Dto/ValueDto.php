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

final readonly class ValueDto extends ExpressionDto
{
    /**
     * @param string|array<array-key,mixed> $value
     */
    public function __construct(
        public string|array|null $value,
    ) {}

    #[\Override]
    public function toArray(): array
    {
        return [
            'class' => 'value',
            'value' => $this->value,
        ];
    }

    #[\Override]
    public static function fromArray(array $array): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (
            !isset($array['class'], $array['value'])
            || $array['class'] !== 'value'
            || (!\is_string($array['value']) && !\is_array($array['value']))
        ) {
            throw new InvalidArgumentException('Invalid array representation for ValueDto.');
        }

        return new self($array['value']);
    }
}
