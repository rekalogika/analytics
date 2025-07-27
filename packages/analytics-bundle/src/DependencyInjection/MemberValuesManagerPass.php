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
use Rekalogika\Analytics\Bundle\MemberValuesManager\ChainMemberValuesManager;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\MemberValuesManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class MemberValuesManagerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $managers = $container
            ->findTaggedServiceIds('rekalogika.analytics.member_values_manager');

        $specificManagers = [];
        $nonSpecificManagers = [];

        foreach (array_keys($managers) as $serviceId) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass()
                ?? throw new InvalidArgumentException(\sprintf('Service "%s" does not have a class', $serviceId));

            if (!class_exists($class)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" not found', $class));
            }

            if (!is_a($class, MemberValuesManager::class, true)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" does not implement "%s".', $class, MemberValuesManager::class));
            }

            if (!is_a($class, ApplicableDimensionsAware::class, true)) {
                $nonSpecificManagers[] = new Reference($serviceId);

                continue;
            }

            $applicableDimensions = $class::getApplicableDimensions();

            foreach ($applicableDimensions as $applicableDimension) {
                $summaryClass = $applicableDimension->getClass();
                $dimension = $applicableDimension->getDimension();

                $key = \sprintf('%s::%s', $summaryClass, $dimension);

                if (isset($specificManagers[$key])) {
                    throw new InvalidArgumentException(\sprintf('Duplicate managers for "%s"', $key));
                }

                $specificManagers[$key] = $serviceId;
            }
        }

        $service = $container->findDefinition(ChainMemberValuesManager::class);

        $service->setArgument(
            '$specificManagerLocator',
            ServiceLocatorTagPass::register($container, $specificManagers),
        );

        $service->setArgument('$nonSpecificManager', $nonSpecificManagers);
    }
}
