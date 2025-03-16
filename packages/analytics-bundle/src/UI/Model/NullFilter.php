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

namespace Rekalogika\Analytics\Bundle\UI\Model;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Symfony\Contracts\Translation\TranslatableInterface;

final class NullFilter implements FilterExpression
{
    public function __construct(
        private readonly SummaryQuery $query,
        private readonly string $dimension,
    ) {}

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        $dimensionField = $this->query->getDimensionChoices()[$this->dimension]
            ?? throw new \InvalidArgumentException(\sprintf('Dimension "%s" not found', $this->dimension));

        return $dimensionField;
    }

    #[\Override]
    public function createExpression(): ?Expression
    {
        return null;
    }
}
