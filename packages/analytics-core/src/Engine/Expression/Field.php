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

namespace Rekalogika\Analytics\Engine\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

final readonly class Field implements Expression
{
    public function __construct(
        private string $field,
    ) {}

    public function getField(): string
    {
        return $this->field;
    }

    #[\Override]
    public function visit(ExpressionVisitor $visitor): mixed
    {
        if (!$visitor instanceof BaseExpressionVisitor) {
            throw new InvalidArgumentException('Visitor must be an instance of BaseExpressionVisitor');
        }

        return $visitor->visitField($this);
    }
}
