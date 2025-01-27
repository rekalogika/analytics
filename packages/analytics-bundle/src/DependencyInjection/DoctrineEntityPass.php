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

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Rekalogika\Analytics\SummaryManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class DoctrineEntityPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $entityManagers = $container->getParameter('doctrine.entity_managers');
        \assert(\is_array($entityManagers));

        // get entity directory
        $directory = $this->getEntityDirectory();

        /**
         * @var string $name
         */
        foreach (array_keys($entityManagers) as $name) {
            $parameterKey = \sprintf('rekalogika.analytics.doctrine.orm.%s_entity_manager', $name);
            $container->setParameter($parameterKey, $name);

            $pass = DoctrineOrmMappingsPass::createAttributeMappingDriver(
                namespaces: ['Rekalogika\Analytics\Model'],
                directories: [$directory],
                managerParameters: [$parameterKey],
                reportFieldsWhereDeclared: true,
            );

            $pass->process($container);

            $container->getParameterBag()->remove($parameterKey);
        }
    }

    private function getEntityDirectory(): string
    {
        $reflection = new \ReflectionClass(SummaryManager::class);
        $fileName = $reflection->getFileName();

        if (false === $fileName) {
            throw new \RuntimeException('Reflection failed');
        }

        return \dirname($fileName, 1) . '/Model';
    }
}
