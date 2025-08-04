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

namespace Rekalogika\PivotTable\TableFramework\Implementation;

use Rekalogika\PivotTable\TableFramework\IdentityStrategy;

final readonly class DefaultIdentityStrategy implements IdentityStrategy
{
    #[\Override]
    public function getMemberSignature(mixed $member): string
    {
        if (\is_object($member)) {
            return hash('xxh128', (string) spl_object_id($member));
        }

        return hash('xxh128', serialize($member));
    }

    #[\Override]
    public function getMembersSignature(array $members): string
    {
        ksort($members);

        $signatureParts = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($members as $dimension => $value) {
            $signatureParts[] = $dimension . ':' . $this->getMemberSignature($value);
        }

        return hash('xxh128', implode(',', $signatureParts));
    }
}
