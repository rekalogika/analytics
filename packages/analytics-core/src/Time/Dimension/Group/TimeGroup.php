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

namespace Rekalogika\Analytics\Time\Dimension\Group;

use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Contracts\Hierarchy\ContextAwareHierarchy;
use Rekalogika\Analytics\Core\Entity\ContextAwareHierarchyTrait;
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\Metadata\Hierarchy;
use Rekalogika\Analytics\Core\ValueResolver\Noop;
use Rekalogika\Analytics\Time\Dimension\System\GregorianDateTime;

#[Embeddable()]
#[Hierarchy()]
class TimeGroup implements ContextAwareHierarchy
{
    use ContextAwareHierarchyTrait;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Group'),
        source: new Noop(),
    )]
    private ?GregorianDateTime $gregorian = null;

    public function getGregorian(): ?GregorianDateTime
    {
        return $this->gregorian;
    }
}
