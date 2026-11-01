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

namespace Rekalogika\Analytics\Metadata\Attribute;

/**
 * Marks the string property of a summary class that stores which grouping set
 * each row belongs to, i.e. which dimensions were grouped by and which were
 * aggregated away when the row was computed. The framework fills and reads
 * this column itself. Exactly one property of a summary class must have this
 * attribute. `BaseSummary` provides it.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Groupings {}
