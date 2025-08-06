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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Registry;

use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultTreeNode;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\Dimensionality;

final class TreeNodeRegistry
{
    /**
     * @var array<string,DefaultTreeNode>
     */
    private array $nodes = [];

    public function __construct() {}

    public function get(
        DefaultCell $cell,
        Dimensionality $dimensionality,
    ): DefaultTreeNode {
        $signature = $this->getSignature(
            cell: $cell,
            dimensionality: $dimensionality,
        );

        return $this->nodes[$signature] ??= new DefaultTreeNode(
            cell: $cell,
            dimensionNames: $dimensionality,
            registry: $this,
        );
    }

    private function getSignature(
        DefaultCell $cell,
        Dimensionality $dimensionality,
    ): string {
        return \sprintf(
            '%s:%s',
            spl_object_id($cell),
            $dimensionality->getSignature(),
        );
    }
}
