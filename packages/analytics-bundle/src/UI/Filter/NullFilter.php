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

namespace Rekalogika\Analytics\Bundle\UI\Filter;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Bundle\UI\Filter;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class NullFilter implements Filter
{
    public function __construct(
        private string $dimension,
        private TranslatableInterface|string $label,
    ) {}

    #[\Override]
    public function getTemplate(): string
    {
        return '@RekalogikaAnalytics/filter/null_filter.html.twig';
    }

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension;
    }

    #[\Override]
    public function getLabel(): TranslatableInterface|string
    {
        return $this->label;
    }

    #[\Override]
    public function createExpression(): ?Expression
    {
        return null;
    }
}
