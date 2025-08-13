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

namespace Rekalogika\Analytics\Engine\SummaryRefresher\Util;

use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\Visitor\AbstractVisitor;

final class CollectAliasesInGroupByVisitor extends AbstractVisitor
{
    /**
     * @var array<string,true>
     */
    private array $aliases = [];

    #[\Override]
    public function visitField(Field $field): mixed
    {
        $this->aliases[$field->getContent()] = true;

        return null;
    }

    /**
     * Returns the collected aliases.
     *
     * @return list<string>
     */
    public function getAliases(): array
    {
        return array_keys($this->aliases);
    }
}
