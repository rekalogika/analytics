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

namespace Rekalogika\Analytics\Metadata\Source;

final readonly class SourceMetadata
{
    /**
     * @var list<class-string>
     */
    private array $allInvolvedSummaryClasses;

    /**
     * @param class-string $class
     * @param array<string,list<class-string>> $propertyToSummaryClasses
     */
    public function __construct(
        private string $class,
        private array $propertyToSummaryClasses,
    ) {
        $allInvolvedSummaryClasses = [];

        foreach ($propertyToSummaryClasses as $summaryClasses) {
            $allInvolvedSummaryClasses = [
                ...$allInvolvedSummaryClasses,
                ...$summaryClasses,
            ];
        }

        $this->allInvolvedSummaryClasses = array_values(array_unique($allInvolvedSummaryClasses));
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return array<string,list<class-string>>
     */
    public function getPropertyToSummaryClasses(): array
    {
        return $this->propertyToSummaryClasses;
    }

    /**
     * @param list<string> $changedProperties
     * @return list<class-string>
     */
    public function getInvolvedSummaryClassesByChangedProperties(
        array $changedProperties,
    ): array {
        $involvedSummaryClasses = [];

        foreach ($changedProperties as $changedProperty) {
            if (isset($this->propertyToSummaryClasses[$changedProperty])) {
                $involvedSummaryClasses = [
                    ...$involvedSummaryClasses,
                    ...$this->propertyToSummaryClasses[$changedProperty],
                ];
            }
        }

        return array_values(array_unique($involvedSummaryClasses));
    }

    /**
     * @return list<class-string>
     */
    public function getAllInvolvedSummaryClasses(): array
    {
        return $this->allInvolvedSummaryClasses;
    }
}
