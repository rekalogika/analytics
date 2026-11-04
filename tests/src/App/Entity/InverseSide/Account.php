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

namespace Rekalogika\Analytics\Tests\App\Entity\InverseSide;

use Doctrine\ORM\Mapping as ORM;

/**
 * The inverse side of a one-to-one association. Its summary reads a property
 * of the owning side.
 */
#[ORM\Entity()]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: AccountProfile::class, mappedBy: 'account')]
    private ?AccountProfile $profile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?AccountProfile
    {
        return $this->profile;
    }

    /**
     * @internal called by AccountProfile
     */
    public function setProfile(?AccountProfile $profile): void
    {
        $this->profile = $profile;
    }
}
