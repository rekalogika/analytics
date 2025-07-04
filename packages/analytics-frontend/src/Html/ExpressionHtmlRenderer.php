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

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Html\Visitor\HtmlRendererExpressionVisitor;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final readonly class ExpressionHtmlRenderer
{
    public function __construct(
        private Htmlifier $htmlifier,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    /**
     * @return list<string>
     */
    public function renderExpression(Query $query): array
    {
        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($query->getFrom());

        $visitor = new HtmlRendererExpressionVisitor(
            htmlifier: $this->htmlifier,
            summaryMetadata: $summaryMetadata,
        );

        $expressions = $query->getWhere();

        $results = [];

        foreach ($expressions as $expression) {
            $result = $visitor->dispatch($expression);

            if (!\is_string($result)) {
                throw new LogicException('Expected string result from expression rendering, got: ' . get_debug_type($result));
            }

            $results[] = $result;
        }

        return $results;
    }
}
