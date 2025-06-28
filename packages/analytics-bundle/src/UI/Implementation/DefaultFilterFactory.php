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

namespace Rekalogika\Analytics\Bundle\UI\Implementation;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Rekalogika\Analytics\Bundle\UI\Filter;
use Rekalogika\Analytics\Bundle\UI\Filter\DateRangeFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\EqualFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\NullFilter;
use Rekalogika\Analytics\Bundle\UI\Filter\NumberRangesFilter;
use Rekalogika\Analytics\Bundle\UI\FilterFactory;
use Rekalogika\Analytics\Bundle\UI\SpecificFilterFactory;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

final readonly class DefaultFilterFactory implements FilterFactory
{
    public function __construct(
        private ContainerInterface $specificFilterFactories,
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    #[\Override]
    public function createFilter(
        string $summaryClass,
        string $dimension,
        array $inputArray,
        ?object $options = null,
    ): Filter {
        $metadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($summaryClass);

        $dimension = $metadata->getDimension($dimension);
        $typeClass = $dimension->getTypeClass();
        $valueResolver = $dimension->getValueResolver();

        if (
            $this->isDoctrineRelation($summaryClass, $dimension->getName())
        ) {
            $filterFactory = $this->getSpecificFilterFactory(EqualFilter::class);
        } elseif ($valueResolver instanceof TimeBinValueResolver) {
            $typeClass = $valueResolver->getTypeClass();

            if (is_a($typeClass, Date::class, true)) {
                $filterFactory = $this->getSpecificFilterFactory(DateRangeFilter::class);
            } else {
                $filterFactory = $this->getSpecificFilterFactory(NumberRangesFilter::class);
            }
        } elseif ($typeClass !== null && enum_exists($typeClass)) {
            $filterFactory = $this->getSpecificFilterFactory(EqualFilter::class);
        } else {
            $filterFactory = $this->getSpecificFilterFactory(NullFilter::class);
        }

        return $filterFactory->createFilter(
            summaryClass: $summaryClass,
            dimension: $dimension->getName(),
            inputArray: $inputArray,
        );
    }

    /**
     * @template T of Filter
     * @param class-string<T> $class
     * @return SpecificFilterFactory<T>
     */
    private function getSpecificFilterFactory(string $class): SpecificFilterFactory
    {
        $filterFactory = $this->specificFilterFactories->get($class);

        if (!$filterFactory instanceof SpecificFilterFactory) {
            throw new InvalidArgumentException(\sprintf(
                'Expected %s, got %s',
                SpecificFilterFactory::class,
                get_debug_type($filterFactory),
            ));
        }

        /** @var SpecificFilterFactory<T> $filterFactory */

        return $filterFactory;
    }

    /**
     * @param class-string $summaryClass
     */
    private function isDoctrineRelation(
        string $summaryClass,
        string $dimension,
    ): bool {
        $doctrineMetadata = $this->managerRegistry
            ->getManagerForClass($summaryClass)
            ?->getClassMetadata($summaryClass);

        if ($doctrineMetadata === null) {
            return false;
        }

        return $doctrineMetadata->hasAssociation($dimension);
    }
}
