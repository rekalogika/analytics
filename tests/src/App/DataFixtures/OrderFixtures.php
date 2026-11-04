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
use Rekalogika\Analytics\Tests\App\Factory\OrderFactory;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\flush_after;

final class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class,
            ItemFixtures::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        flush_after(function (): void {
            OrderFactory::createSequence(self::getOrderAttributes(200));
        });

        $manager->flush();
    }

    /**
     * Spreads the orders evenly over the months of 2023 and 2024, at a
     * random time in each month. With random times over the whole period, a
     * month is occasionally left without orders, and the tests querying it
     * fail at random. The first and last day of the month are left out, so
     * that the order stays in the month in any time zone.
     *
     * @return list<array{time:\DateTimeImmutable}>
     */
    private static function getOrderAttributes(int $count): array
    {
        $attributes = [];
        $firstMonth = new \DateTimeImmutable('2023-01-01 00:00:00', new \DateTimeZone('UTC'));

        for ($i = 0; $i < $count; $i++) {
            $month = $firstMonth->modify(\sprintf('+%d months', $i % 24));
            $start = $month->modify('+1 day');
            $end = $month->modify('last day of this month')->setTime(0, 0);

            $attributes[] = [
                'time' => \DateTimeImmutable::createFromInterface(
                    faker()->dateTimeBetween(
                        $start->format('Y-m-d H:i:s'),
                        $end->format('Y-m-d H:i:s'),
                        'UTC',
                    ),
                ),
            ];
        }

        return $attributes;
    }
}
