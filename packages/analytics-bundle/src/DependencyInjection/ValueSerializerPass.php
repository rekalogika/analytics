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

namespace Rekalogika\Analytics\Bundle\DependencyInjection;

use Rekalogika\Analytics\Bundle\Common\ApplicableDimensionsAware;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class ValueSerializerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $managers = $container
            ->findTaggedServiceIds('rekalogika.analytics.value_serializer');

        $specificServices = [];
        $nonSpecificServices = [];

        foreach (array_keys($managers) as $serviceId) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass()
                ?? throw new InvalidArgumentException(\sprintf('Service "%s" does not have a class', $serviceId));

            if (!class_exists($class)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" not found', $class));
            }

            if (!is_a($class, ValueSerializer::class, true)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" does not implement "%s".', $class, ValueSerializer::class));
            }

            if (!is_a($class, ApplicableDimensionsAware::class, true)) {
                $nonSpecificServices[] = new Reference($serviceId);

                continue;
            }

            $applicableDimensions = $class::getApplicableDimensions();

            foreach ($applicableDimensions as $applicableDimension) {
                $summaryClass = $applicableDimension->getClass();
                $dimension = $applicableDimension->getDimension();

                $key = \sprintf('%s::%s', $summaryClass, $dimension);

                if (isset($specificServices[$key])) {
                    throw new InvalidArgumentException(\sprintf('Duplicate managers for "%s"', $key));
                }

                $specificServices[$key] = $serviceId;
            }
        }

        $service = $container->findDefinition('rekalogika.analytics.value_serializer.chain');

        $service->setArgument(
            '$specificServiceLocator',
            ServiceLocatorTagPass::register($container, $specificServices),
        );

        $service->setArgument('$nonSpecificServices', $nonSpecificServices);
    }
}
