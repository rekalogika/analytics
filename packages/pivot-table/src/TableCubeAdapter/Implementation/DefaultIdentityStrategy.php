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

namespace Rekalogika\PivotTable\TableCubeAdapter\Implementation;

use Rekalogika\PivotTable\TableCubeAdapter\IdentityStrategy;

final readonly class DefaultIdentityStrategy implements IdentityStrategy
{
    #[\Override]
    public function getTupleSignature(array $tuple): string
    {
        ksort($tuple);
        $signatureParts = [];

        foreach ($tuple as $dimension) {
            $signatureParts[] = \sprintf(
                '%s:%s',
                $dimension->getName(),
                $this->getMemberSignature($dimension->getMember()),
            );
        }

        return hash('xxh128', implode(',', $signatureParts));
    }

    #[\Override]
    public function getMemberSignature(mixed $member): string
    {
        if (\is_object($member)) {
            return hash('xxh128', (string) spl_object_id($member));
        }

        return hash('xxh128', serialize($member));
    }
}
