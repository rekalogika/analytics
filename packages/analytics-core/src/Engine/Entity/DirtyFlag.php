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
 * A flag indicating that a partition in a summary table needs refreshing.
 */
#[ORM\Entity()]
#[ORM\Table(name: 'rekalogika_summary_dirty')]
#[ORM\Index(fields: ['class', 'level', 'key'])]
class DirtyFlag
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private \DateTimeInterface $created;

    /**
     * Level and key are partition properties, indicating the partition that
     * needs refreshing. Null level and keys means there are new records.
     *
     * @param class-string $class The summary class that needs refreshing.
     */
    public function __construct(
        #[ORM\Column(length: 255, nullable: false)]
        private string $class,
        #[ORM\Column(type: Types::SMALLINT, nullable: true)]
        private ?int $level,
        #[ORM\Column(type: Types::BIGINT, nullable: true)]
        private ?int $key,
    ) {
        $this->created = new \DateTimeImmutable();
    }

    public function getSignature(): string
    {
        return \sprintf('%s(%d,%s)', $this->class, $this->level ?? '-', $this->key ?? '-');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getKey(): ?int
    {
        return $this->key;
    }
}
