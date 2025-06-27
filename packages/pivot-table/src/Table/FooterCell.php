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

namespace Rekalogika\PivotTable\Table;

/**
 * Represents a HTML footer cell. This is conceptual only as HTML does not have
 * a specific footer cell element. It should be rendered as a regular cell
 * (<td> or <th>).
 */
interface FooterCell extends Cell {}
