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

final readonly class CompositeExpressionDto extends ExpressionDto
{
    /**
     * @param list<ExpressionDto> $expressions
     */
    public function __construct(
        public string $type,
        public array $expressions,
    ) {}

    #[\Override]
    public function toArray(): array
    {
        return [
            'class' => 'compositeExpression',
            'type' => $this->type,
            'expressions' => array_map(
                fn(ExpressionDto $expr) => $expr->toArray(),
                $this->expressions,
            ),
        ];
    }

    #[\Override]
    public static function fromArray(array $array): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (
            !isset($array['class'], $array['type'], $array['expressions'])
            || $array['class'] !== 'compositeExpression'
            || !\is_string($array['type'])
            || !\is_array($array['expressions'])
        ) {
            throw new InvalidArgumentException('Invalid array representation for CompositeExpressionDto.');
        }

        $expressions = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($array['expressions'] as $exprArray) {
            /**
             * @psalm-suppress MixedArgument
             * @phpstan-ignore argument.type
             */
            $expressions[] = ExpressionDto::fromArray($exprArray);
        }

        return new self(
            type: $array['type'],
            expressions: $expressions,
        );
    }
}
