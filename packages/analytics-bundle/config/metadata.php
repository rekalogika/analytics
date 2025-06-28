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

use Rekalogika\Analytics\Metadata\Implementation\CachingAttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultAttributeCollectionFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultDimensionGroupMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultDimensionMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Implementation\DefaultSummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    //
    // attribute collection factory
    //

    $services->alias(
        'rekalogika.analytics.attribute_collection_factory',
        'rekalogika.analytics.metadata.attribute_collection_factory',
    );

    $services
        ->set('rekalogika.analytics.metadata.attribute_collection_factory')
        ->class(DefaultAttributeCollectionFactory::class);

    $services
        ->set('rekalogika.analytics.metadata.attribute_collection_factory.caching')
        ->class(CachingAttributeCollectionFactory::class)
        ->decorate('rekalogika.analytics.metadata.attribute_collection_factory')
        ->args([
            '$decorated' => service('.inner'),
        ]);

    //
    // dimension metadata factory
    //

    $services
        ->set('rekalogika.analytics.dimension_metadata_factory')
        ->class(DefaultDimensionMetadataFactory::class)
        ->args([
            '$attributeCollectionFactory' => service('rekalogika.analytics.metadata.attribute_collection_factory'),
            '$dimensionGroupMetadataFactory' => service('rekalogika.analytics.dimension_class_metadata_factory'),
        ])
    ;

    //
    // dimension class metadata factory
    //

    $services
        ->set('rekalogika.analytics.dimension_class_metadata_factory')
        ->class(DefaultDimensionGroupMetadataFactory::class)
        ->args([
            '$attributeCollectionFactory' => service('rekalogika.analytics.metadata.attribute_collection_factory'),
        ])
    ;

    //
    // summary metadata factory
    //

    $services->alias(
        SummaryMetadataFactory::class,
        'rekalogika.analytics.summary_metadata_factory',
    );

    $services
        ->set('rekalogika.analytics.summary_metadata_factory')
        ->class(DefaultSummaryMetadataFactory::class)
        ->args([
            '$managerRegistry' => service('doctrine'),
            '$attributeCollectionFactory' => service('rekalogika.analytics.metadata.attribute_collection_factory'),
            '$dimensionMetadataFactory' => service('rekalogika.analytics.dimension_metadata_factory'),
        ])
    ;

    //
    // source metadata factory
    //

    $services->alias(
        SourceMetadataFactory::class,
        'rekalogika.analytics.source_metadata_factory',
    );

    $services
        ->set('rekalogika.analytics.source_metadata_factory')
        ->class(DefaultSourceMetadataFactory::class)
        ->args([
            '$summaryMetadataFactory' => service(SummaryMetadataFactory::class),
        ])
    ;
};
