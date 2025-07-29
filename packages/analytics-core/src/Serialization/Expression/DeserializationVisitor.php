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
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;

/**
 * Visitor for converting an expression into a form that can be serialized.
 */
final class DeserializationVisitor extends BaseVisitor
{
    #[\Override]
    public function walkValue(Value $value): mixed
    {
        $currentDimension = $this->currentDimension;

        if ($currentDimension === null) {
            throw new UnexpectedValueException('Current dimension is not set.');
        }

        /** @psalm-suppress MixedAssignment */
        $identifier = $value->getValue();

        if (\is_array($identifier)) {
            return array_map(
                fn(mixed $v): mixed => (new Value($v))->visit($this),
                $identifier,
            );
        }

        if (!\is_string($identifier)) {
            throw new UnexpectedValueException(\sprintf(
                'Expected identifier to be a string, got "%s".',
                get_debug_type($identifier),
            ));
        }

        return $this->valueSerializer->deserialize(
            class: $this->summaryClass,
            dimension: $currentDimension,
            identifier: $identifier,
        );
    }
}
