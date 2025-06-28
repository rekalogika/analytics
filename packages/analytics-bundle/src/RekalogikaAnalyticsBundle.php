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
use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Numberifier;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Core\Doctrine\Function\BustFunction;
use Rekalogika\Analytics\Engine\Doctrine\Function\GroupingConcatFunction;
use Rekalogika\Analytics\Engine\Doctrine\Function\NextValFunction;
use Rekalogika\Analytics\Engine\Doctrine\Function\TruncateBigIntFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllAddAggregateFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllCardinalityFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllHashFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllUnionAggregateFunction;
use Rekalogika\Analytics\Time\Doctrine\Function\TimeBinFunction;
use Rekalogika\Analytics\Uuid\Doctrine\TruncateUuidToBigintFunction;
use Rekalogika\Analytics\Uuid\Doctrine\UuidToDateTimeFunction;
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
        $container->import('../config/metadata.php');
        $container->import('../config/engine.php');
        $container->import('../config/bundle.php');
        $container->import('../config/ui.php');

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

        $builder->registerForAutoconfiguration(Stringifier::class)
            ->addTag('rekalogika.analytics.stringifier');

        $builder->registerForAutoconfiguration(Htmlifier::class)
            ->addTag('rekalogika.analytics.htmlifier');

        $builder->registerForAutoconfiguration(Numberifier::class)
            ->addTag('rekalogika.analytics.numberifier');

        $builder->registerForAutoconfiguration(Cellifier::class)
            ->addTag('rekalogika.analytics.cellifier');

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
        $this->prependDQLFunctions($builder);
        $this->prependMigrations($builder);
    }

    private function prependDQLFunctions(ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('doctrine', [
            'orm' => [
                'dql' => [
                    'string_functions' => [
                        'REKALOGIKA_NEXTVAL' => NextValFunction::class,
                        'REKALOGIKA_BUST' => BustFunction::class,
                        'REKALOGIKA_TRUNCATE_BIGINT'  => TruncateBigIntFunction::class,
                        'REKALOGIKA_GROUPING_CONCAT' => GroupingConcatFunction::class,
                        'REKALOGIKA_HLL_ADD_AGG' => HllAddAggregateFunction::class,
                        'REKALOGIKA_HLL_UNION_AGG' => HllUnionAggregateFunction::class,
                        'REKALOGIKA_HLL_HASH' => HllHashFunction::class,
                    ],
                    'numeric_functions' => [
                        'REKALOGIKA_TIME_BIN' => TimeBinFunction::class,
                        'REKALOGIKA_TRUNCATE_UUID_TO_BIGINT' => TruncateUuidToBigintFunction::class,
                        'REKALOGIKA_HLL_CARDINALITY' => HllCardinalityFunction::class,
                    ],
                    'datetime_functions' => [
                        'REKALOGIKA_UUID_TO_DATETIME' => UuidToDateTimeFunction::class,
                    ],
                ],
            ],
        ]);
    }

    private function prependMigrations(ContainerBuilder $builder): void
    {
        $migrationsPaths = [];

        try {
            $bustPath = (new \ReflectionClass(BustFunction::class))->getFileName();

            if ($bustPath === false) {
                throw new \ReflectionException('Could not get file name for BustFunction');
            }

            $bustPath = \dirname($bustPath, 2) . '/Migrations';
            $migrationsPaths['Rekalogika\Analytics\Core\Doctrine\Migrations'] = $bustPath;
        } catch (\ReflectionException) {
        }

        try {
            $timeBinPath = (new \ReflectionClass(TimeBinFunction::class))->getFileName();

            if ($timeBinPath === false) {
                throw new \ReflectionException('Could not get file name for TimeBinFunction');
            }

            $timeBinPath = \dirname($timeBinPath, 2) . '/Migrations';
            $migrationsPaths['Rekalogika\Analytics\Time\Doctrine\Migrations'] = $timeBinPath;
        } catch (\ReflectionException) {
        }

        $builder->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => $migrationsPaths,
        ]);
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
