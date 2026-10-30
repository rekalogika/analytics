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

namespace Rekalogika\Analytics\UX\PanelBundle;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class RekalogikaPanelBundle extends AbstractBundle
{
    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @param array<array-key,mixed> $config
     */
    #[\Override]
    public function loadExtension(
        array $config,
        ContainerConfigurator $configurator,
        ContainerBuilder $container,
    ): void {
        $configurator->import('../config/services.php');

        $container->registerForAutoconfiguration(FilterFactory::class)
            ->addTag('rekalogika.analytics.specific_filter_factory');

        $container->registerForAutoconfiguration(FilterResolver::class)
            ->addTag('rekalogika.analytics.ux-panel.filter_resolver');
    }

    /**
     * @see https://symfony.com/doc/current/frontend/create_ux_bundle.html
     */
    #[\Override]
    public function prependExtension(
        ContainerConfigurator $configurator,
        ContainerBuilder $container,
    ): void {
        $this->prependTwig($container);
        $this->prependAssetMapper($container);
    }

    private function prependTwig(ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../templates' => 'RekalogikaAnalyticsUXPanel',
            ],
        ]);
    }

    /**
     * @see https://symfony.com/doc/current/frontend/create_ux_bundle.html
     */
    private function prependAssetMapper(ContainerBuilder $builder): void
    {
        if (!$this->isAssetMapperAvailable($builder)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@rekalogika/analytics-ux-panel',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // Check that FrameworkBundle 6.3 or higher is installed.
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');

        if (!\is_array($bundlesMetadata)) {
            throw new LogicException('Kernel bundles metadata not found.');
        }

        if (!isset($bundlesMetadata['FrameworkBundle']) || !\is_array($bundlesMetadata['FrameworkBundle'])) {
            throw new LogicException('FrameworkBundle metadata not found.');
        }

        $dir = $bundlesMetadata['FrameworkBundle']['path'] ?? throw new LogicException('FrameworkBundle path not found.');

        if (!\is_string($dir)) {
            throw new LogicException('FrameworkBundle path is not a string.');
        }

        return is_file($dir . '/Resources/config/asset_mapper.php');
    }
}
