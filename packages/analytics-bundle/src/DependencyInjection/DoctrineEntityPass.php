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
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Core\Entity\Summary;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Time\TimeBin;
use Rekalogika\Analytics\Uuid\Partition\UuidV7IntegerPartition;
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

        $directories = $this->getEntityDirectories();

        /**
         * @var string $name
         */
        foreach (array_keys($entityManagers) as $name) {
            $parameterKey = \sprintf('rekalogika.analytics.doctrine.orm.%s_entity_manager', $name);
            $container->setParameter($parameterKey, $name);

            $pass = DoctrineOrmMappingsPass::createAttributeMappingDriver(
                namespaces: [
                    'Rekalogika\Analytics\Core\Entity',
                    'Rekalogika\Analytics\Core\Partition',
                    'Rekalogika\Analytics\Engine\Entity',
                    'Rekalogika\Analytics\Time\Dimension',
                    'Rekalogika\Analytics\Uuid\Partition',
                ],
                directories: $directories,
                managerParameters: [$parameterKey],
                reportFieldsWhereDeclared: true,
            );

            $pass->process($container);

            $container->getParameterBag()->remove($parameterKey);
        }
    }

    /**
     * @return list<string>
     */
    private function getEntityDirectories(): array
    {
        $directories = [];

        // core

        $reflection = new \ReflectionClass(Summary::class);
        $fileName = $reflection->getFileName();

        if (false === $fileName) {
            throw new LogicException('Reflection failed');
        }

        $directories[] = \dirname($fileName);
        $directories[] = \dirname($fileName, 2) . '/Partition';

        // time

        if (class_exists(TimeBin::class)) {
            $reflection = new \ReflectionClass(TimeBin::class);
            $fileName = $reflection->getFileName();

            if (false === $fileName) {
                throw new LogicException('Reflection failed');
            }

            $directories[] = \dirname($fileName, 2) . '/Dimension';
        }

        // uuid

        if (class_exists(UuidV7IntegerPartition::class)) {
            $reflection = new \ReflectionClass(UuidV7IntegerPartition::class);
            $fileName = $reflection->getFileName();

            if (false === $fileName) {
                throw new LogicException('Reflection failed');
            }

            $directories[] = \dirname($fileName);
        }

        // Engine

        if (class_exists(DirtyFlag::class)) {
            $reflection = new \ReflectionClass(DirtyFlag::class);
            $fileName = $reflection->getFileName();

            if (false === $fileName) {
                throw new LogicException('Reflection failed');
            }

            $directories[] = \dirname($fileName);
        }

        return $directories;
    }
}
