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
 * @implements \IteratorAggregate<Field|FieldSet>
 */
final class RollUp implements Item, \IteratorAggregate
{
    /**
     * @var list<Field|FieldSet>
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
                    fn(Field|FieldSet $field): string => $field->getSignature(),
                    $this->fields,
                ),
            ),
        );
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fields);
    }

    public function addField(Field|FieldSet $field): void
    {
        $this->fields[] = $field;
    }

    public function flatten(): GroupingSet
    {
        $groupingSet = new GroupingSet();

        $count = \count($this->fields);

        foreach (range($count, 0) as $i) {
            $members = \array_slice($this->fields, 0, $i);

            $fieldSet = new FieldSet();

            foreach ($members as $member) {
                if ($member instanceof Field) {
                    $fieldSet->addField($member);
                } else {
                    foreach ($member as $field) {
                        $fieldSet->addField($field);
                    }
                }
            }

            $groupingSet->addItem($fieldSet);
        }

        return $groupingSet;
    }
}
