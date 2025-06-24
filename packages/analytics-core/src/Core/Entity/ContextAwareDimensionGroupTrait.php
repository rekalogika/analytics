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

namespace Rekalogika\Analytics\Core\Entity;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Context\DimensionGroupContext;
use Rekalogika\Analytics\Contracts\DimensionGroup\ContextAwareDimensionGroup;

/**
 * @phpstan-require-implements ContextAwareDimensionGroup
 */
trait ContextAwareDimensionGroupTrait
{
    private ?DimensionGroupContext $context = null;

    final public function setContext(DimensionGroupContext $context): void
    {
        $this->context = $context;
    }

    protected function getContext(): DimensionGroupContext
    {
        if (null === $this->context) {
            throw new LogicException('Context is not set.');
        }

        return $this->context;
    }
}
