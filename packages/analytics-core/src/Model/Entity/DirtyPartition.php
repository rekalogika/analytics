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

#[ORM\Entity()]
#[ORM\Table(name: 'rekalogika_dirty')]
class DirtyPartition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private \DateTimeInterface $time;

    /**
     * @param class-string $class
     */
    public function __construct(
        #[ORM\Column(length: 255, nullable: false)]
        private string $class,
        #[ORM\Column(nullable: false)]
        private int $level,
        #[ORM\Column(nullable: false)]
        private string $key,
    ) {
        $this->time = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        if ($this->id === null) {
            throw new \LogicException('The ID is not set.');
        }

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

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }
}
