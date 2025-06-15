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

use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class DistinctValuesResolverPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $resolvers = $container
            ->findTaggedServiceIds('rekalogika.analytics.distinct_values_resolver');

        $specificResolvers = [];
        $nonSpecificResolvers = [];

        foreach (array_keys($resolvers) as $serviceId) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass()
                ?? throw new InvalidArgumentException(\sprintf('Service "%s" does not have a class', $serviceId));

            if (!class_exists($class)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" not found', $class));
            }

            if (!is_a($class, DistinctValuesResolver::class, true)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" does not implement DistinctValuesResolver', $class));
            }

            $applicableDimensions = $class::getApplicableDimensions();

            if ($applicableDimensions !== null) {
                foreach ($applicableDimensions as [$summaryClass, $dimension]) {
                    $key = \sprintf('%s::%s', $summaryClass, $dimension);

                    if (isset($specificResolvers[$key])) {
                        throw new InvalidArgumentException(\sprintf('Duplicate resolver for "%s"', $key));
                    }

                    $specificResolvers[$key] = $serviceId;
                }
            } else {
                $nonSpecificResolvers[] = new Reference($serviceId);
            }
        }

        $service = $container->findDefinition(DistinctValuesResolver::class);

        $service->setArgument(
            '$specificResolverLocator',
            ServiceLocatorTagPass::register($container, $specificResolvers),
        );

        $service->setArgument('$nonSpecificResolvers', $nonSpecificResolvers);
    }
}
