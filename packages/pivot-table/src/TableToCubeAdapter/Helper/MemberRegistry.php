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

namespace Rekalogika\PivotTable\TableToCubeAdapter\Helper;

use Rekalogika\PivotTable\TableToCubeAdapter\IdentityStrategy;
use Rekalogika\PivotTable\TableToCubeAdapter\Model\TableToCubeAdapterCube;

final class MemberRegistry
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $members = [];

    public function __construct(
        private IdentityStrategy $identityStrategy,
    ) {}

    public function registerMember(string $dimensionName, mixed $member): void
    {
        $signature = $this->identityStrategy->getMemberSignature($member);

        if (isset($this->members[$signature])) {
            throw new \RuntimeException(\sprintf(
                'Member with signature "%s" already exists.',
                $signature,
            ));
        }

        $this->members[$dimensionName][$signature] = $member;
    }

    public function registerCubeMembers(TableToCubeAdapterCube $cube): void
    {
        foreach ($cube->getTuple() as $dimension) {
            $dimensionName = $dimension->getName();
            $this->registerMember($dimensionName, $dimension->getMember());
        }
    }

    /**
     * @return iterable<mixed>
     */
    public function getMembers(string $dimensionName): iterable
    {
        if (!isset($this->members[$dimensionName])) {
            return [];
        }

        return array_values($this->members[$dimensionName]);
    }
}
