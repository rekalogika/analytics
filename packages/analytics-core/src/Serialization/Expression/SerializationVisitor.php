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

namespace Rekalogika\Analytics\Serialization\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;

/**
 * Visitor for converting an expression into a form that can be serialized.
 */
final class SerializationVisitor extends BaseVisitor
{
    #[\Override]
    public function walkValue(Value $value): mixed
    {
        $currentDimension = $this->currentDimension;

        if ($currentDimension === null) {
            throw new UnexpectedValueException('Current dimension is not set.');
        }

        /** @psalm-suppress MixedAssignment */
        $realValue = $value->getValue();

        if (\is_array($realValue)) {
            return array_map(
                fn(mixed $v): mixed => (new Value($v))->visit($this),
                $realValue,
            );
        }

        return $this->valueSerializer->serialize(
            class: $this->summaryClass,
            dimension: $currentDimension,
            value: $value->getValue(),
        );
    }
}
