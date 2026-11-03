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

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Rekalogika\Analytics\Bundle\RekalogikaAnalyticsBundle;
use Rekalogika\Analytics\UX\PanelBundle\RekalogikaPanelBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\UX\Chartjs\ChartjsBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Turbo\TurboBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;
use Zenstruck\Messenger\Test\ZenstruckMessengerTestBundle;

return [
    FrameworkBundle::class => ['all' => true],
    DebugBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    WebProfilerBundle::class => ['all' => true],
    DoctrineFixturesBundle::class => ['all' => true],
    ZenstruckFoundryBundle::class => ['all' => true],
    MakerBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    StimulusBundle::class => ['all' => true],
    RekalogikaAnalyticsBundle::class => ['all' => true],
    TurboBundle::class => ['all' => true],
    DAMADoctrineTestBundle::class => ['all' => true],
    ZenstruckMessengerTestBundle::class => ['all' => true],
    ChartjsBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    RekalogikaPanelBundle::class => ['all' => true],
];
