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

namespace Rekalogika\Analytics\TimeDimensionHierarchy\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

trait SmallintTypeTrait
{
    public function getSQLDeclaration(
        array $column,
        AbstractPlatform $platform,
    ): string {
        return $platform->getSmallIntTypeDeclarationSQL($column);
    }
}
