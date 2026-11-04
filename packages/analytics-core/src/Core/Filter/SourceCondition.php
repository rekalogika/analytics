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

namespace Rekalogika\Analytics\Core\Filter;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Summary\SourceFilterExpression;
use Rekalogika\Analytics\Core\ValueResolver\CustomExpression;

/**
 * A DQL condition in the filter of a summary. Property paths of the source
 * entity are written in brackets, and are resolved the same way as in
 * `CustomExpression`:
 *
 * ```php
 * #[Analytics\Summary(
 *     sourceClass: Order::class,
 *     filter: new SourceCondition('[customer.*] INSTANCE OF App\Entity\IndividualCustomer'),
 * )]
 * ```
 *
 * The paths in brackets are tracked: a change to one of them refreshes the
 * summary. Paths written without brackets, e.g. in a subquery, are not.
 */
final readonly class SourceCondition implements SourceFilterExpression
{
    private CustomExpression $expression;

    public function __construct(string $condition)
    {
        $this->expression = new CustomExpression($condition);
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        return $this->expression->getExpression($context);
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->expression->getInvolvedProperties();
    }

    #[\Override]
    public function visit(ExpressionVisitor $visitor): mixed
    {
        throw new LogicException(\sprintf(
            '"%s" can only be used in the filter of a summary.',
            self::class,
        ));
    }
}
