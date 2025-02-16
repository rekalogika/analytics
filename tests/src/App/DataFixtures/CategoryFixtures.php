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
use Rekalogika\Analytics\Tests\App\Factory\CategoryFactory;

final class CategoryFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        CategoryFactory::createMany(5);

        $manager->flush();
    }
}
