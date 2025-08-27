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

namespace Rekalogika\Analytics\Frontend\Html;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Frontend\Exception\FrontendWrapperException;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Html\Visitor\ExpressionRendererVisitor;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final readonly class PredicateRenderer
{
    public function __construct(
        private Htmlifier $htmlifier,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    /**
     * @return list<string>
     */
    public function renderPredicate(Query $query): array
    {
        try {
            $summaryMetadata = $this->summaryMetadataFactory
                ->getSummaryMetadata($query->getFrom());

            $visitor = new ExpressionRendererVisitor(
                htmlifier: $this->htmlifier,
                summaryMetadata: $summaryMetadata,
            );

            $expressions = $query->getDice();

            if ($expressions === null) {
                return [];
            }

            if (!$expressions instanceof CompositeExpression) {
                throw new LogicException('Expected CompositeExpression, got: ' . get_debug_type($expressions));
            }

            if ($expressions->getType() !== CompositeExpression::TYPE_AND) {
                throw new LogicException('Expected AND CompositeExpression, got: ' . $expressions->getType());
            }

            $results = [];

            foreach ($expressions->getExpressionList() as $expression) {
                $result = $visitor->dispatch($expression);

                if (!\is_string($result)) {
                    throw new LogicException('Expected string result from expression rendering, got: ' . get_debug_type($result));
                }

                $results[] = $result;
            }

            return $results;
        } catch (\Throwable $e) {
            throw FrontendWrapperException::selectiveWrap($e);
        }
    }
}
