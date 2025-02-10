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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

/**
 * @internal
 */
final readonly class ResultTuple
{
    /**
     * @var array<string,ResultValue>
     */
    private array $dimensions;

    /**
     * @param array<string,ResultValue> $dimensions
     */
    public function __construct(
        array $dimensions,
    ) {
        $this->dimensions = array_filter($dimensions, static function (ResultValue $value): bool {
            return $value->getField() !== '@values';
        });
    }

    public function isSame(self $other): bool
    {
        $dimensions = $this->getDimensions();
        $otherDimensions = $other->getDimensions();

        foreach ($dimensions as $key => $value) {
            if (!\array_key_exists($key, $otherDimensions)) {
                return false;
            }

            $otherValue = $otherDimensions[$key];

            if (!$value->isSame($otherValue)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string,ResultValue>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    // public function getSignature(): string
    // {
    //     $members = [];

    //     /** @psalm-suppress MixedAssignment */
    //     foreach ($this->dimensions as $dimension => $resultValue) {
    //         $value = $resultValue->getValue();

    //         if (\is_scalar($value)) {
    //             $members[$dimension] = $value;
    //         } elseif (\is_object($value)) {
    //             $members[$dimension] = spl_object_id($value);
    //         } else {
    //             throw new \RuntimeException('Unsupported value type');
    //         }
    //     }

    //     return hash('xxh128', serialize($members));
    // }
}
