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

namespace Rekalogika\Analytics\Engine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'rekalogika_summary_properties')]
class SummaryProperties
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastId = null;

    /**
     * The time just before the latest key of the source was read to update
     * `lastId`, or to find that there are no source records after it. Every
     * source record with a key greater than `lastId` was inserted after this
     * time.
     */
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sourceChecked = null;

    /**
     * The time the refresh agent is scheduled to run next.
     */
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $scheduledRefresh = null;

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

    public function getSourceChecked(): ?\DateTimeImmutable
    {
        return $this->sourceChecked;
    }

    public function setSourceChecked(?\DateTimeImmutable $sourceChecked): void
    {
        $this->sourceChecked = $sourceChecked;
    }

    public function getScheduledRefresh(): ?\DateTimeImmutable
    {
        return $this->scheduledRefresh;
    }

    public function setScheduledRefresh(?\DateTimeImmutable $scheduledRefresh): void
    {
        $this->scheduledRefresh = $scheduledRefresh;
    }
}
