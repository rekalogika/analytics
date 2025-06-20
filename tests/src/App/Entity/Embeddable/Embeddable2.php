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

namespace Rekalogika\Analytics\Tests\App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable()]
class Embeddable2
{
    #[ORM\Embedded()]
    private Embeddable3 $embeddable3;

    public function __construct()
    {
        $this->embeddable3 = new Embeddable3();
    }

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmbeddable3(): Embeddable3
    {
        return $this->embeddable3;
    }
}
