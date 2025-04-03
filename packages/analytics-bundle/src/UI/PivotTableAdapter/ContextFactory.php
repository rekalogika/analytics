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

namespace Rekalogika\Analytics\Bundle\UI\PivotTableAdapter;

use Rekalogika\Analytics\Bundle\Formatter\Context;
use Rekalogika\Analytics\Bundle\Formatter\PropertyType;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeLabel;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeMember;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeValue;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeWrapper;

final readonly class ContextFactory
{
    public function createContext(NodeWrapper $nodeWrapper): Context
    {
        $type = match ($nodeWrapper::class) {
            NodeValue::class => PropertyType::Value,
            NodeMember::class => PropertyType::Member,
            NodeLabel::class => PropertyType::Label,
            default => throw new \LogicException('Invalid node wrapper type.'),
        };

        return new Context(
            node: $nodeWrapper->getNode(),
            type: $type,
        );
    }
}
