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

/**
 * Records the time an upper-level partition was refreshed by the refresh
 * agent. Used to enforce the cooldown period of the upper-level partitions.
 * Records older than the cooldown period are no longer relevant, and are
 * removed by the refresh agent.
 */
#[ORM\Entity()]
#[ORM\Table(name: 'rekalogika_summary_refresh_log')]
#[ORM\Index(fields: ['class', 'level', 'key'])]
class RefreshLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private ?int $id = null;

    /**
     * @param class-string $class The summary class.
     */
    public function __construct(
        #[ORM\Column(length: 255, nullable: false)]
        private string $class,
        #[ORM\Column(type: Types::SMALLINT, nullable: false)]
        private int $level,
        #[ORM\Column(type: Types::BIGINT, nullable: false)]
        private int $key,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
        private \DateTimeImmutable $refreshed,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getKey(): int
    {
        return $this->key;
    }

    public function getRefreshed(): \DateTimeImmutable
    {
        return $this->refreshed;
    }
}
