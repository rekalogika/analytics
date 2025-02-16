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

namespace Rekalogika\Analytics\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'rekalogika_summary_properties')]
final class SummaryTableProperties
{
    #[ORM\Column(length: 255)]
    private ?string $lastId = null;

    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 255, nullable: false)]
        private string $summaryClass,
    ) {}

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    public function getLastId(): ?string
    {
        return $this->lastId;
    }

    public function setLastId(string $lastId): void
    {
        $this->lastId = $lastId;
    }
}
