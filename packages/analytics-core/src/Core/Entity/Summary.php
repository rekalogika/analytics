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

namespace Rekalogika\Analytics\Core\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\Contracts\Summary\ContextAwareSummary;
use Rekalogika\Analytics\Metadata\Attribute as Analytics;

/**
 * Super class for summary entity. Contains properties that exist in all summary
 * entities. A summary entity can opt not to extend this class, but it is
 * required to have the properties defined in this class.
 */
#[ORM\MappedSuperclass()]
abstract class Summary implements ContextAwareSummary
{
    use ContextAwareSummaryTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    protected int $__id;

    #[ORM\Column]
    #[Analytics\Groupings]
    protected string $__groupings;
}
