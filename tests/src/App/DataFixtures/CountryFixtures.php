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
use Rekalogika\Analytics\Tests\App\Factory\CountryFactory;
use Rekalogika\Analytics\Tests\App\Factory\RegionFactory;

class CountryFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            RegionFixtures::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->createCountry('FR', 'France', 'Europe');
        $this->createCountry('DE', 'Germany', 'Europe');
        $this->createCountry('MX', 'Mexico', 'America');
        $this->createCountry('CA', 'Canada', 'America');
        $this->createCountry('CN', 'China', 'Asia');
        $this->createCountry('JP', 'Japan', 'Asia');

        $manager->flush();
    }

    private function createCountry(string $code, string $name, string $region): void
    {
        CountryFactory::createOne([
            'code' => $code,
            'name' => $name,
            'region' => RegionFactory::find(['name' => $region]),
        ]);
    }
}
