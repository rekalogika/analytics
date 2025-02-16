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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

/**
 * A flag indicating that a partition in a summary table needs refreshing.
 */
#[ORM\Entity()]
#[ORM\Table(name: 'rekalogika_summary_dirty')]
#[ORM\Index(fields: ['class', 'level', 'key'])]
final class DirtyFlag
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, nullable: false)]
    private string $id;

    /**
     * Level and key are partition properties, indicating the partition that
     * needs refreshing. Null level and keys means there are new records.
     *
     * @param class-string $class
     */
    public function __construct(
        #[ORM\Column(length: 255, nullable: false)]
        private string $class,
        #[ORM\Column(nullable: true)]
        private ?int $level,
        #[ORM\Column(nullable: true)]
        private ?string $key,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getSignature(): string
    {
        return \sprintf('%s(%d,%s)', $this->class, $this->level ?? '-', $this->key ?? '-');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUuid(): UuidV7
    {
        return UuidV7::fromString($this->id);
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

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->getUuid()->getDateTime();
    }
}
