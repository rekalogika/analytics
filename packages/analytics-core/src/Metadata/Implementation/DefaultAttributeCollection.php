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

namespace Rekalogika\Analytics\Metadata\Implementation;

use Rekalogika\Analytics\Common\Exception\RuntimeException;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Rekalogika\Analytics\Metadata\Util\AttributeUtil;

final readonly class DefaultAttributeCollection implements AttributeCollection
{
    /**
     * @var array<class-string,non-empty-list<object>>
     */
    private array $classToAttributes;

    /**
     * @param iterable<object> $attributes
     */
    public function __construct(iterable $attributes)
    {
        $classToAttributes = [];

        foreach ($attributes as $attribute) {
            $classes = AttributeUtil::getAllClassesFromObject($attribute);

            foreach ($classes as $class) {
                $classToAttributes[$class][] = $attribute;
            }
        }

        $this->classToAttributes = $classToAttributes;
    }

    #[\Override]
    public function getAttribute(string $class): object
    {
        return $this->tryGetAttribute($class) ?? throw new RuntimeException(
            "Attribute of class '$class' not found in collection.",
        );
    }

    #[\Override]
    public function tryGetAttribute(string $class): ?object
    {
        return $this->getAttributes($class)[0] ?? null;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return list<T>
     */
    #[\Override]
    public function getAttributes(string $class): array
    {
        /** @var list<T> */
        return $this->classToAttributes[$class] ?? [];
    }

    #[\Override]
    public function hasAttribute(string $class): bool
    {
        return isset($this->classToAttributes[$class]);
    }
}
