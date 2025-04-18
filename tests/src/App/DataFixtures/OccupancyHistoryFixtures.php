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
use Doctrine\Persistence\ObjectManager;
use Rekalogika\Analytics\Tests\App\Entity\Gender;
use Rekalogika\Analytics\Tests\App\Factory\OccupancyHistoryFactory;

final class OccupancyHistoryFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $baseDate = new \DateTimeImmutable('2023-01-01');

        foreach (range(1, 200) as $i) {
            $date = $baseDate->modify(\sprintf('+%d days', $i));

            OccupancyHistoryFactory::createOne([
                'date' => $date,
                'gender' => Gender::Male,
            ]);

            OccupancyHistoryFactory::createOne([
                'date' => $date,
                'gender' => Gender::Female,
            ]);
        }

        $manager->flush();
    }
}
