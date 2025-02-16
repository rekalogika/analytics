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

namespace Rekalogika\Analytics\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Rekalogika\Analytics\Tests\App\Factory\ItemFactory;

final class ItemFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            CountryFixtures::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        ItemFactory::createMany(50);

        $manager->flush();
    }
}
