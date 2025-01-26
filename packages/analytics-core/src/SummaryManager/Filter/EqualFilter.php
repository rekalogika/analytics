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

namespace Rekalogika\Analytics\SummaryManager\Filter;

/**
 * @template T of mixed
 */
final class EqualFilter implements Filter
{
    /**
     * @var list<T>
     */
    private array $values = [];

    /**
     * @return list<T>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param list<T> $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }
}
