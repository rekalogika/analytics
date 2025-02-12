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

namespace Rekalogika\Analytics\Bundle;

use Rekalogika\Analytics\Bundle\DependencyInjection\DistinctValuesResolverPass;
use Rekalogika\Analytics\Bundle\DependencyInjection\DoctrineEntityPass;
use Rekalogika\Analytics\DistinctValuesResolver;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class RekalogikaAnalyticsBundle extends AbstractBundle
{
    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DoctrineEntityPass());
        $container->addCompilerPass(new DistinctValuesResolverPass());
    }

    /**
     * @param array<array-key,mixed> $config
     */
    #[\Override]
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->import('../config/services.php');

        $builder->registerForAutoconfiguration(DistinctValuesResolver::class)
            ->addTag('rekalogika.analytics.distinct_values_resolver');
    }

    /**
     * @see https://symfony.com/doc/current/frontend/create_ux_bundle.html
     */
    #[\Override]
    public function prependExtension(
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        if (!$this->isAssetMapperAvailable($builder)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@rekalogika/analytics-bundle',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');

        if (!\is_array($bundlesMetadata)) {
            throw new \LogicException('Kernel bundles metadata not found.');
        }

        if (!isset($bundlesMetadata['FrameworkBundle']) || !\is_array($bundlesMetadata['FrameworkBundle'])) {
            throw new \LogicException('FrameworkBundle metadata not found.');
        }

        $dir = $bundlesMetadata['FrameworkBundle']['path'] ?? throw new \LogicException('FrameworkBundle path not found.');

        if (!\is_string($dir)) {
            throw new \LogicException('FrameworkBundle path is not a string.');
        }

        return is_file($dir . '/Resources/config/asset_mapper.php');
    }
}
