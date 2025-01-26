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

namespace Rekalogika\DoctrineAdvancedGroupBy;

/**
 * @implements \IteratorAggregate<Field>
 */
final class FieldSet implements Item, \IteratorAggregate
{
    /**
     * @var list<Field>
     */
    private array $fields = [];

    #[\Override]
    public function count(): int
    {
        return \count($this->fields);
    }

    #[\Override]
    public function getSignature(): string
    {
        return hash(
            'xxh128',
            self::class . implode(
                '',
                array_map(
                    fn(Field $field): string => $field->getSignature(),
                    $this->fields,
                ),
            ),
        );
    }

    public static function create(Field ...$fields): self
    {
        $fieldSet = new self();

        foreach ($fields as $field) {
            $fieldSet->addField($field);
        }

        return $fieldSet;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fields);
    }

    public function addField(Field $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return list<Field>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
