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

namespace Rekalogika\Analytics\Tests\App\Entity\ClassCast;

use Doctrine\ORM\Mapping as ORM;
use Rekalogika\Analytics\Tests\App\Entity\Customer;

/**
 * An entity whose identifier is not named `id`, related to an entity with
 * inheritance whose identifier is named `id`.
 */
#[ORM\Entity()]
class Shipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $shipmentId = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    public function getShipmentId(): ?int
    {
        return $this->shipmentId;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }
}
