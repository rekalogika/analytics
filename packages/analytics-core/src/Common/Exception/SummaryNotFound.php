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

namespace Rekalogika\Analytics\Common\Exception;

final class SummaryNotFound extends NotFoundException
{
    public function __construct(string $summary)
    {
        parent::__construct(\sprintf('Summary "%s" not found.', $summary));
    }
}
