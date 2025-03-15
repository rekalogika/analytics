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

namespace Rekalogika\Analytics\TimeDimensionHierarchy\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Rekalogika\Analytics\RecurringTimeInterval;
use Rekalogika\Analytics\TimeInterval;

abstract class AbstractTimeDimensionType extends Type
{
    /**
     * @return class-string<TimeInterval|RecurringTimeInterval>
     */
    abstract protected function getClass(): string;

    #[\Override]
    abstract public function getSQLDeclaration(
        array $column,
        AbstractPlatform $platform,
    ): string;

    #[\Override]
    final public function convertToPHPValue(
        mixed $value,
        AbstractPlatform $platform,
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (!\is_int($value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The value must be an integer, %s given.',
                \gettype($value),
            ));
        }

        return ($this->getClass())::createFromDatabaseValue($value);
    }

    #[\Override]
    final public function convertToDatabaseValue(
        mixed $value,
        AbstractPlatform $platform,
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (\is_int($value)) {
            return $value;
        }

        $class = $this->getClass();

        if (\is_object($value) && is_a($value, $class, true)) {
            /** @var TimeInterval|RecurringTimeInterval $value */
            return $value->getDatabaseValue();
        }

        throw new \InvalidArgumentException(\sprintf(
            'The value must be an instance of %s.',
            $class,
        ));
    }

    final public function getName(): string
    {
        return $this->getClass();
    }
}
