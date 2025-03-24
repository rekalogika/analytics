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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker;

use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final class DimensionFactory
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions = [];

    public function createDimension(
        string $key,
        TranslatableInterface $label,
        mixed $member,
        mixed $rawMember,
        mixed $displayMember,
    ): DefaultDimension {
        if (\is_object($rawMember)) {
            $signature = hash(
                'xxh128',
                serialize([$key, spl_object_id($rawMember)]),
            );
        } else {
            $signature = hash(
                'xxh128',
                serialize([$key, $rawMember]),
            );
        }

        return $this->dimensions[$signature] ??= new DefaultDimension(
            key: $key,
            label: $label,
            member: $member,
            rawMember: $rawMember,
            displayMember: $displayMember,
        );
    }
}
