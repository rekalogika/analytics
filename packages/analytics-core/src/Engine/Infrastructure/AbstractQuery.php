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

namespace Rekalogika\Analytics\Engine\Infrastructure;

use Rekalogika\Analytics\SimpleQueryBuilder\QueryComponents;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

abstract class AbstractQuery
{
    private ?QueryComponents $queryComponents = null;

    protected function __construct(
        private SimpleQueryBuilder $simpleQueryBuilder,
    ) {}

    public function __clone()
    {
        $this->simpleQueryBuilder = clone $this->simpleQueryBuilder;

        if (null !== $this->queryComponents) {
            $this->queryComponents = clone $this->queryComponents;
        }
    }

    protected function getSimpleQueryBuilder(): SimpleQueryBuilder
    {
        return $this->simpleQueryBuilder;
    }

    protected function resolve(string $path): string
    {
        return $this->simpleQueryBuilder->resolve($path);
    }

    public function getQueryComponents(): QueryComponents
    {
        if (null === $this->queryComponents) {
            $this->queryComponents = $this->simpleQueryBuilder->getQueryComponents();
        }

        return $this->queryComponents;
    }
}
