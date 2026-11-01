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

namespace Rekalogika\Analytics\Contracts\Exception;

/**
 * Thrown when the given class is not a known summary class, i.e. it does not
 * have the `Summary` attribute or is not a mapped Doctrine entity.
 */
final class SummaryNotFound extends NotFoundException
{
    public function __construct(string $summary)
    {
        parent::__construct(\sprintf('Summary "%s" not found.', $summary));
    }
}
