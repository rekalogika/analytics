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

namespace Rekalogika\Analytics\Tests\App;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Rekalogika\Analytics\Bundle\RekalogikaAnalyticsBundle;
use Rekalogika\Analytics\UX\PanelBundle\RekalogikaPanelBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\Chartjs\ChartjsBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Turbo\TurboBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;
use Zenstruck\Messenger\Test\ZenstruckMessengerTestBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        registerContainerConfiguration as private baseRegisterContainerConfiguration;
    }

    public function __construct(
        string $environment,
        bool $debug,
    ) {
        parent::__construct($environment, $debug);
        $this->environment = $environment;
        $this->debug = $debug;
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void {}

    #[\Override]
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DebugBundle();
        yield new DoctrineBundle();
        yield new TwigBundle();
        yield new WebProfilerBundle();
        yield new DoctrineFixturesBundle();
        yield new ZenstruckFoundryBundle();
        yield new MakerBundle();
        yield new MonologBundle();
        yield new StimulusBundle();
        yield new RekalogikaAnalyticsBundle();
        yield new TurboBundle();
        yield new DAMADoctrineTestBundle();
        yield new ZenstruckMessengerTestBundle();
        yield new ChartjsBundle();
        yield new DoctrineMigrationsBundle();
        yield new RekalogikaPanelBundle();
    }

    #[\Override]
    public function getProjectDir(): string
    {
        return __DIR__ . '/../../';
    }

    public function getConfigDir(): string
    {
        return __DIR__ . '/../../config/';
    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $this->baseRegisterContainerConfiguration($loader);

        $loader->load(function (ContainerBuilder $container): void {});
    }
}
