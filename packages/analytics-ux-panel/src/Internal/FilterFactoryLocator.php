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

namespace Rekalogika\Analytics\UX\PanelBundle\Internal;

use Psr\Container\ContainerInterface;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Rekalogika\Analytics\UX\PanelBundle\FilterFactory;

final readonly class FilterFactoryLocator
{
    public function __construct(private ContainerInterface $container) {}

    /**
     * @template T of Filter
     * @param class-string<T> $id
     * @return FilterFactory<T>
     */
    public function locate(string $id): FilterFactory
    {
        if (!class_exists($id) || !is_a($id, Filter::class, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Filter factory "%s" does not exist',
                $id,
            ));
        }

        $filterFactory = $this->container->get($id);

        if (!$filterFactory instanceof FilterFactory) {
            throw new InvalidArgumentException(\sprintf(
                'Filter factory "%s" must implement %s',
                $id,
                FilterFactory::class,
            ));
        }

        /** @var FilterFactory<T> $filterFactory */

        return $filterFactory;
    }
}
