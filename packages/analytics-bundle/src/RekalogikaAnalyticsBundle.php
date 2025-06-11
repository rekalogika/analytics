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
use Rekalogika\Analytics\Bundle\DependencyInjection\DoctrineTypesPass;
use Rekalogika\Analytics\Bundle\Formatter\BackendCellifier;
use Rekalogika\Analytics\Bundle\Formatter\BackendHtmlifier;
use Rekalogika\Analytics\Bundle\Formatter\BackendNumberifier;
use Rekalogika\Analytics\Bundle\Formatter\BackendStringifier;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
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
        $container->addCompilerPass(new DoctrineTypesPass());
    }

    #[\Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $root = $definition->rootNode();
        \assert($root instanceof ArrayNodeDefinition);

        $root->children()->integerNode('query_result_limit')
            ->defaultValue(5000)
            ->min(1)
            ->info('The maximum number of results to be returned from the query. If a query exceeds this limit, OverflowException will be thrown.');

        $root->children()->integerNode('filling_nodes_limit')
            ->defaultValue(10000)
            ->min(1)
            ->info('The maximum number of nodes created due to gaps between values. If the amount of created nodes exceeds this limit, OverflowException will be thrown.');
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

        $container->parameters()
            ->set(
                'rekalogika.analytics.query_result_limit',
                $config['query_result_limit'],
            )
            ->set(
                'rekalogika.analytics.filling_nodes_limit',
                $config['filling_nodes_limit'],
            );

        $builder->registerForAutoconfiguration(DistinctValuesResolver::class)
            ->addTag('rekalogika.analytics.distinct_values_resolver');

        $builder->registerForAutoconfiguration(BackendStringifier::class)
            ->addTag('rekalogika.analytics.backend_stringifier');

        $builder->registerForAutoconfiguration(BackendHtmlifier::class)
            ->addTag('rekalogika.analytics.backend_htmlifier');

        $builder->registerForAutoconfiguration(BackendNumberifier::class)
            ->addTag('rekalogika.analytics.backend_numberifier');

        $builder->registerForAutoconfiguration(BackendCellifier::class)
            ->addTag('rekalogika.analytics.backend_cellifier');

        $builder->registerForAutoconfiguration(SpecificFilterFactory::class)
            ->addTag('rekalogika.analytics.specific_filter_factory');
    }

    /**
     * @see https://symfony.com/doc/current/frontend/create_ux_bundle.html
     */
    #[\Override]
    public function prependExtension(
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $this->prependTwig($builder);
        $this->prependAssetMapper($builder);
    }

    private function prependTwig(ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../templates' => 'RekalogikaAnalytics',
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
