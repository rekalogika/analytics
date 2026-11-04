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

namespace Rekalogika\Analytics\Metadata\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Summary\SourceFilterExpression;

/**
 * Validates the filter of a summary, and returns the property paths of the
 * source entity it reads.
 */
final readonly class SourceFilterUtil
{
    /**
     * The comparison operators supported in a filter.
     */
    public const OPERATORS = [
        Comparison::EQ,
        Comparison::NEQ,
        Comparison::LT,
        Comparison::LTE,
        Comparison::GT,
        Comparison::GTE,
        Comparison::IN,
        Comparison::NIN,
        Comparison::CONTAINS,
        Comparison::STARTS_WITH,
        Comparison::ENDS_WITH,
    ];

    private function __construct() {}

    /**
     * @return list<string>
     * @throws MetadataException If the filter contains an unsupported
     * expression or operator.
     */
    public static function getInvolvedProperties(Expression $filter): array
    {
        if ($filter instanceof Comparison) {
            if (!\in_array($filter->getOperator(), self::OPERATORS, true)) {
                throw new MetadataException(\sprintf(
                    'Operator "%s" is not supported in the filter of a summary, supported operators are: %s',
                    $filter->getOperator(),
                    implode(', ', self::OPERATORS),
                ));
            }

            return [$filter->getField()];
        }

        if ($filter instanceof CompositeExpression) {
            $properties = [];

            foreach ($filter->getExpressionList() as $expression) {
                $properties = [
                    ...$properties,
                    ...self::getInvolvedProperties($expression),
                ];
            }

            return array_values(array_unique($properties));
        }

        if ($filter instanceof SourceFilterExpression) {
            return $filter->getInvolvedProperties();
        }

        throw new MetadataException(\sprintf(
            'Expression "%s" is not supported in the filter of a summary, use a Comparison, a CompositeExpression, or a SourceFilterExpression.',
            $filter::class,
        ));
    }
}
