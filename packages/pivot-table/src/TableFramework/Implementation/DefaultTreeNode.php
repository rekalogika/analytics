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

namespace Rekalogika\PivotTable\TableFramework\Implementation;

use Rekalogika\PivotTable\Contracts\Row;
use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\TableFramework\Manager;

final readonly class DefaultTreeNode implements TreeNode
{
    /**
     * @param list<string> $path Dimension name path
     */
    public static function create(
        Manager $manager,
        array $path,
    ): self {
        if (!\in_array('@values', $path, true)) {
            $path[] = '@values';
        }

        $row = $manager
            ->getRowRepository()
            ->getRowOrFail([]);

        return new self(
            manager: $manager,
            tuple: [],
            descendantPath: $path,
            value: self::resolveValueFromRow($row),
        );
    }


    private static function resolveValueFromRow(Row $row): mixed
    {
        $tuple = iterator_to_array($row->getDimensions(), true);
        $measures = iterator_to_array($row->getMeasures(), true);

        return self::resolveValueFromTupleAndMeasures($tuple, $measures);
    }

    /**
     * @param array<string,mixed> $tuple
     * @param array<string,mixed> $measures
     * @return mixed
     */
    private static function resolveValueFromTupleAndMeasures(
        array $tuple,
        array $measures,
    ): mixed {
        // get measure name

        $measureName = $tuple['@values'] ?? null;

        if ($measureName === null) {
            return null;
        }

        if (!\is_string($measureName)) {
            throw new \InvalidArgumentException(\sprintf(
                'Measure name must be a string, "%s" given.',
                \gettype($measureName),
            ));
        }

        // get value

        return $measures[$measureName]
            ?? throw new \InvalidArgumentException(\sprintf(
                'Value for measure "%s" not found in row.',
                $measureName,
            ));
    }

    /**
     * @param Manager $manager
     * @param array<string,mixed> $tuple
     * @param list<string> $descendantPath
     */
    private function __construct(
        private Manager $manager,
        private array $tuple,
        private array $descendantPath,
        private mixed $value,
    ) {}

    public function createNullChild(string $childKey, mixed $childItem): TreeNode
    {
        $tuple = $this->tuple;
        /** @psalm-suppress MixedAssignment */
        $tuple[$childKey] = $childItem;

        return new self(
            manager: $this->manager,
            tuple: $tuple,
            descendantPath: $this->descendantPath,
            value: null,
        );
    }

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->descendantPath === [];
    }

    #[\Override]
    public function getPath(): array
    {
        return array_keys($this->tuple);
    }

    #[\Override]
    public function getKey(): string
    {
        return array_key_last($this->tuple) ?? '';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->manager->getLegend($this->getKey());
    }

    #[\Override]
    public function getItem(): mixed
    {
        $key = $this->getKey();

        if ($key === '') {
            return null;
        }

        if (!\array_key_exists($key, $this->tuple)) {
            throw new \InvalidArgumentException(\sprintf(
                'Item for key "%s" not found in tuple.',
                $key,
            ));
        }

        if ($key === '@values') {
            if (!\is_string($this->tuple[$key])) {
                throw new \InvalidArgumentException(\sprintf(
                    'Expected string for key "%s", "%s" given.',
                    $key,
                    \gettype($this->tuple[$key]),
                ));
            }

            return $this->manager->getLegend($this->tuple[$key]);
        }

        return $this->tuple[$key];
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        $members = $this->manager
            ->getDimensionRepository()
            ->getMembers($dimensionName);

        $descendantPath = $this->descendantPath;

        // remove from $descendantPath until $dimensionName
        $level = array_search($dimensionName, $descendantPath, true);

        if ($level === false) {
            throw new \InvalidArgumentException(\sprintf(
                'Dimension name "%s" not found in descendant path.',
                $dimensionName,
            ));
        }
        $level += 1; // level is 1-based, not 0-based
        $descendantPath = \array_slice($descendantPath, $level);

        /** @psalm-suppress MixedAssignment */
        foreach ($members as $member) {
            $tuple = $this->tuple;
            $tuple[$dimensionName] = $member;

            $row = $this->manager
                ->getRowRepository()
                ->getRow($tuple);

            if ($row === null) {
                continue;
            }

            $measures = iterator_to_array($row->getMeasures(), true);
            $value = self::resolveValueFromTupleAndMeasures($tuple, $measures);

            yield new self(
                manager: $this->manager,
                tuple: $tuple,
                descendantPath: $descendantPath,
                value: $value,
            );
        }
    }

    #[\Override]
    public function rollUp(array $keys): TreeNode
    {
        $tuple = $this->tuple;
        $descendantPath = $this->descendantPath;

        foreach ($keys as $key) {
            if (!\array_key_exists($key, $tuple)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Key "%s" not found in tuple.',
                    $key,
                ));
            }

            unset($tuple[$key]);
            $descendantPath[] = $key;
        }

        $row = $this->manager
            ->getRowRepository()
            ->getRow($tuple);

        if ($row === null) {
            throw new \InvalidArgumentException(\sprintf(
                'Row with tuple "%s" not found.',
                json_encode($tuple, JSON_THROW_ON_ERROR),
            ));
        }

        $measures = iterator_to_array($row->getMeasures(), true);

        /** @psalm-suppress MixedAssignment */
        $value = self::resolveValueFromTupleAndMeasures($tuple, $measures);

        return new self(
            manager: $this->manager,
            tuple: $tuple,
            descendantPath: $descendantPath,
            value: $value,
        );
    }
}
