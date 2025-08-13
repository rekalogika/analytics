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
use Rekalogika\Analytics\Bundle\DependencyInjection\ValueSerializerPass;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Core\Doctrine\Function\BustFunction;
use Rekalogika\Analytics\Core\Doctrine\Function\InFunction;
use Rekalogika\Analytics\Core\Doctrine\Function\IsNotNullFunction;
use Rekalogika\Analytics\Core\Doctrine\Function\IsNullFunction;
use Rekalogika\Analytics\Engine\Doctrine\Function\GroupingConcatFunction;
use Rekalogika\Analytics\Engine\Doctrine\Function\NextValFunction;
use Rekalogika\Analytics\Engine\Doctrine\Function\NullFunction;
use Rekalogika\Analytics\Engine\Doctrine\Function\TruncateBigIntFunction;
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Rekalogika\Analytics\PostgreSQLExtra\Doctrine\Function\FirstFunction;
use Rekalogika\Analytics\PostgreSQLExtra\Doctrine\Function\LastFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllAddAggregateFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllCardinalityFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllHashFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function\HllUnionAggregateFunction;
use Rekalogika\Analytics\PostgreSQLHll\Doctrine\HllType;
use Rekalogika\Analytics\Time\Doctrine\Function\TimeBinFunction;
use Rekalogika\Analytics\Time\Doctrine\Function\TimeBinMbwWeekFunction;
use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\Uuid\Doctrine\TruncateUuidToBigintFunction;
use Rekalogika\Analytics\Uuid\Doctrine\UuidToDateTimeFunction;
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
        $container->addCompilerPass(new ValueSerializerPass());
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

        $root->children()->scalarNode('table_theme')
            ->defaultValue('@RekalogikaAnalyticsFrontend/renderer.html.twig')
            ->info('The theme to be used for rendering pivot tables. This theme should be a Twig template that extends the `@RekalogikaAnalyticsFrontend/renderer.html.twig` template.');
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

        if (class_exists(TableRenderer::class)) {
            $container->import('../config/frontend.php');
        }

        if (interface_exists(TimeBin::class)) {
            $container->import('../config/time.php');
        }

        $container->parameters()
            ->set(
                'rekalogika.analytics.query_result_limit',
                $config['query_result_limit'],
            )
            ->set(
                'rekalogika.analytics.filling_nodes_limit',
                $config['filling_nodes_limit'],
            )
            ->set(
                'rekalogika.analytics.table_theme',
                $config['table_theme'],
            )
        ;

        $builder->registerForAutoconfiguration(ValueSerializer::class)
            ->addTag('rekalogika.analytics.value_serializer');

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
        $this->prependDQLFunctions($builder);
        $this->prependMigrations($builder);
    }

    private function prependDQLFunctions(ContainerBuilder $builder): void
    {
        $types = [];
        $mappingTypes = [];

        //
        // common
        //

        $stringFunctions = [
            'REKALOGIKA_NEXTVAL' => NextValFunction::class,
            'REKALOGIKA_BUST' => BustFunction::class,
            'REKALOGIKA_TRUNCATE_BIGINT' => TruncateBigIntFunction::class,
            'REKALOGIKA_GROUPING_CONCAT' => GroupingConcatFunction::class,
            'REKALOGIKA_IS_NULL' => IsNullFunction::class,
            'REKALOGIKA_IS_NOT_NULL' => IsNotNullFunction::class,
            'REKALOGIKA_IN' => InFunction::class,
            'REKALOGIKA_NULL' => NullFunction::class,
        ];

        $numericFunctions = [
            'REKALOGIKA_TRUNCATE_UUID_TO_BIGINT' => TruncateUuidToBigintFunction::class,
        ];

        $datetimeFunctions = [
            'REKALOGIKA_UUID_TO_DATETIME' => UuidToDateTimeFunction::class,
        ];

        //
        // time
        //

        if (class_exists(TimeBinFunction::class)) {
            $stringFunctions = [
                ...$stringFunctions,
                'REKALOGIKA_TIME_BIN' => TimeBinFunction::class,
                'REKALOGIKA_TIME_BIN_MBW_WEEK' => TimeBinMbwWeekFunction::class,
            ];
        }

        //
        // PostgreSQLHll
        //

        if (class_exists(HllAddAggregateFunction::class)) {
            $stringFunctions = [
                ...$stringFunctions,
                'REKALOGIKA_HLL_ADD_AGG' => HllAddAggregateFunction::class,
                'REKALOGIKA_HLL_UNION_AGG' => HllUnionAggregateFunction::class,
                'REKALOGIKA_HLL_HASH' => HllHashFunction::class,
            ];

            $numericFunctions = [
                ...$numericFunctions,
                'REKALOGIKA_HLL_CARDINALITY' => HllCardinalityFunction::class,
            ];

            $types['rekalogika_hll'] = HllType::class;
            $mappingTypes['hll'] = 'rekalogika_hll';
        }

        //
        // PostgreSQLExtra
        //

        if (class_exists(FirstFunction::class)) {
            $stringFunctions = [
                ...$stringFunctions,
                'REKALOGIKA_FIRST' => FirstFunction::class,
                'REKALOGIKA_LAST' => LastFunction::class,
            ];
        }

        //
        // finalize
        //

        $builder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => $types,
                'mapping_types' => $mappingTypes,
            ],
            'orm' => [
                'dql' => [
                    'string_functions' => $stringFunctions,
                    'numeric_functions' => $numericFunctions,
                    'datetime_functions' => $datetimeFunctions,
                ],
            ],
        ]);
    }

    private function prependMigrations(ContainerBuilder $builder): void
    {
        $migrationsPaths = [];

        //
        // core
        //

        try {
            $bustPath = (new \ReflectionClass(BustFunction::class))->getFileName();

            if ($bustPath === false) {
                throw new \ReflectionException('Could not get file name for BustFunction');
            }

            $bustPath = \dirname($bustPath, 2) . '/Migrations';
            $migrationsPaths['Rekalogika\Analytics\Core\Doctrine\Migrations'] = $bustPath;
        } catch (\ReflectionException) {
        }

        //
        // time
        //

        try {
            $timeBinPath = (new \ReflectionClass(TimeBinFunction::class))->getFileName();

            if ($timeBinPath === false) {
                throw new \ReflectionException('Could not get file name for TimeBinFunction');
            }

            $timeBinPath = \dirname($timeBinPath, 2) . '/Migrations';
            $migrationsPaths['Rekalogika\Analytics\Time\Doctrine\Migrations'] = $timeBinPath;
        } catch (\ReflectionException) {
        }

        //
        // postgresql extra
        //

        try {
            $firstPath = (new \ReflectionClass(FirstFunction::class))->getFileName();

            if ($firstPath === false) {
                throw new \ReflectionException('Could not get file name for FirstFunction');
            }

            $firstPath = \dirname($firstPath, 2) . '/Migrations';
            $migrationsPaths['Rekalogika\Analytics\PostgreSQLExtra\Doctrine\Migrations'] = $firstPath;
        } catch (\ReflectionException) {
        }

        //
        // finalize
        //

        $builder->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => $migrationsPaths,
        ]);
    }

    private function prependTwig(ContainerBuilder $builder): void
    {
        //
        // frontend
        //

        if (!class_exists(TableRenderer::class)) {
            return;
        }

        $path = (new \ReflectionClass(TableRenderer::class))->getFileName();

        if ($path === false) {
            throw new InvalidArgumentException('Could not get file name for PivotTableRenderer');
        }

        $templatePath = \dirname($path, 3) . '/templates';


        $builder->prependExtensionConfig('twig', [
            'paths' => [
                $templatePath => 'RekalogikaAnalyticsFrontend',
            ],
        ]);
    }
}
