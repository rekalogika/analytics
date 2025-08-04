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
    private mixed $value;

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
            row: $row,
        );
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
        Row $row,
    ) {
        // get measure name

        $measureName = $tuple['@values'] ?? null;

        if ($measureName === null) {
            $this->value = null;

            return;
        }

        if (!\is_string($measureName)) {
            throw new \InvalidArgumentException(\sprintf(
                'Measure name must be a string, "%s" given.',
                \gettype($measureName),
            ));
        }

        // get value

        $measures = iterator_to_array($row->getMeasures(), true);

        $this->value = $measures[$measureName]
            ?? throw new \InvalidArgumentException(\sprintf(
                'Value for measure "%s" not found in row.',
                $measureName,
            ));
    }

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->descendantPath === [];
    }

    #[\Override]
    public function getKey(): string
    {
        return array_key_last($this->tuple) ?? '';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        $key = $this->getKey();

        if ($key === '@values') {
            return $this->manager->getMeasureLabel();
        }

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
    public function getChildren(int $level = 1): iterable
    {
        $dimensionName = $this->descendantPath[$level - 1] ?? null;

        if ($dimensionName === null) {
            yield from [];
            return;
            // throw new \InvalidArgumentException(\sprintf(
            //     'Dimension name for level %d not found in descendant path.',
            //     $level,
            // ));
        }

        $members = $this->manager
            ->getDimensionRepository()
            ->getMembers($dimensionName);

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

            yield new self(
                manager: $this->manager,
                tuple: $tuple,
                descendantPath: \array_slice($this->descendantPath, $level),
                row: $row,
            );
        }
    }
}
