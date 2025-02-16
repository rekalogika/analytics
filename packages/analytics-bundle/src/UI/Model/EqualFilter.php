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

final class EqualFilter
{
    /**
     * @var list<mixed>
     */
    private array $values = [];

    /**
     * @param list<mixed> $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * @return list<mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
