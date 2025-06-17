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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query\Helper;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Common\Exception\LogicException;

final class Groupings
{
    /**
     * @var array<string,string>
     */
    private array $grouping = [];

    public function add(string $property, string $expression): void
    {
        if (\in_array($expression, $this->grouping, true)) {
            $previousProperty = array_search($expression, $this->grouping, true);

            if ($previousProperty === false) {
                throw new LogicException("Should never happen");
            }

            throw new InvalidArgumentException(\sprintf(
                'Expression "%s" already exists for property "%s", and you are trying to add the same expression for property "%s". Two properties with the same expression in the same summary class is not allowed because it will confuse the database.',
                $expression,
                $property,
                $previousProperty,
            ));
        }

        $this->grouping[$property] = $expression;
    }

    public function getExpression(): string
    {
        $grouping = $this->grouping;
        ksort($grouping);

        return implode(', ', $grouping);
    }
}
