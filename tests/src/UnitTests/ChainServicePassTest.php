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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Bundle\DependencyInjection\DistinctValuesResolverPass;
use Rekalogika\Analytics\Bundle\DependencyInjection\ValueSerializerPass;
use Rekalogika\Analytics\Bundle\DistinctValuesResolver\ChainDistinctValuesResolver;
use Rekalogika\Analytics\Bundle\Serialization\ChainValueSerializer;
use Rekalogika\Analytics\Contracts\DistinctValuesResolver;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Engine\DistinctValuesResolver\DoctrineDistinctValuesResolver;
use Rekalogika\Analytics\Engine\Serialization\DoctrineValueSerializer;
use Rekalogika\Analytics\Tests\UnitTests\ChainServicePass\SpecificDistinctValuesResolver;
use Rekalogika\Analytics\Tests\UnitTests\ChainServicePass\SpecificValueSerializer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ChainServicePassTest extends TestCase
{
    /**
     * @return iterable<string,array{CompilerPassInterface,string,string,class-string}>
     */
    public static function providePasses(): iterable
    {
        yield 'value serializer' => [
            new ValueSerializerPass(),
            'rekalogika.analytics.value_serializer.chain',
            'rekalogika.analytics.value_serializer',
            DoctrineValueSerializer::class,
        ];

        yield 'distinct values resolver' => [
            new DistinctValuesResolverPass(),
            'rekalogika.analytics.distinct_values_resolver.chain',
            'rekalogika.analytics.distinct_values_resolver',
            DoctrineDistinctValuesResolver::class,
        ];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('providePasses')]
    public function testNonSpecificServicesAreSortedByPriority(
        CompilerPassInterface $pass,
        string $chainId,
        string $tag,
        string $class,
    ): void {
        $container = new ContainerBuilder();
        $container->register($chainId);

        $container->register('builtin', $class)
            ->addTag($tag, ['priority' => -1000]);

        $container->register('default', $class)
            ->addTag($tag);

        $container->register('high', $class)
            ->addTag($tag, ['priority' => 10]);

        $pass->process($container);

        /** @var list<Reference> */
        $services = $container->getDefinition($chainId)
            ->getArgument('$nonSpecificServices');

        $this->assertSame(
            ['high', 'default', 'builtin'],
            array_map(strval(...), $services),
        );
    }

    public function testSpecificValueSerializerIsResolvedFromLocator(): void
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new ValueSerializerPass());

        $container->register('rekalogika.analytics.value_serializer.chain', ChainValueSerializer::class)
            ->setPublic(true);

        $container->register('specific', SpecificValueSerializer::class)
            ->addTag('rekalogika.analytics.value_serializer');

        $container->compile();

        $chain = $container->get('rekalogika.analytics.value_serializer.chain');
        $this->assertInstanceOf(ValueSerializer::class, $chain);

        $this->assertSame(
            'serialized by specific',
            $chain->serialize(\stdClass::class, 'specific', 'foo'),
        );

        $this->assertSame(
            'deserialized by specific',
            $chain->deserialize(\stdClass::class, 'specific', 'foo'),
        );
    }

    public function testSpecificDistinctValuesResolverIsResolvedFromLocator(): void
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DistinctValuesResolverPass());

        $container->register('rekalogika.analytics.distinct_values_resolver.chain', ChainDistinctValuesResolver::class)
            ->setPublic(true);

        $container->register('specific', SpecificDistinctValuesResolver::class)
            ->addTag('rekalogika.analytics.distinct_values_resolver');

        $container->compile();

        $chain = $container->get('rekalogika.analytics.distinct_values_resolver.chain');
        $this->assertInstanceOf(DistinctValuesResolver::class, $chain);

        $this->assertSame(
            ['specific' => 'resolved by specific'],
            $chain->getDistinctValues(\stdClass::class, 'specific', 10),
        );
    }
}
