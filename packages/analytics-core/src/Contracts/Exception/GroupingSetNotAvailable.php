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

namespace Rekalogika\Analytics\Contracts\Exception;

use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Thrown when a query needs a combination of dimensions that is not stored in
 * the summary table, i.e. no stored grouping set contains every dimension
 * involved in the query.
 */
final class GroupingSetNotAvailable extends InvalidArgumentException implements TranslatableInterface
{
    /**
     * @param class-string $summaryClass
     * @param list<string> $dimensions The dimensions involved in the query.
     * @param list<TranslatableInterface> $dimensionLabels The labels of the
     * dimensions involved in the query.
     * @param list<list<string>> $availableSets A sample of the stored grouping
     * sets, as lists of dimension names.
     * @param int $availableSetsCount The total number of stored grouping sets.
     */
    public function __construct(
        private readonly string $summaryClass,
        private readonly array $dimensions,
        private readonly array $dimensionLabels,
        private readonly array $availableSets,
        private readonly int $availableSetsCount,
    ) {
        $sets = array_map(
            static fn(array $set): string => '(' . implode(', ', $set) . ')',
            $availableSets,
        );

        $remaining = $availableSetsCount - \count($availableSets);

        if ($remaining > 0) {
            $sets[] = \sprintf('... and %d more', $remaining);
        }

        parent::__construct(\sprintf(
            'Summary "%s" does not store a grouping set that contains all of these dimensions: %s. Available grouping sets: %s',
            $summaryClass,
            implode(', ', $dimensions),
            implode(', ', $sets),
        ));
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $labels = array_map(
            static fn(TranslatableInterface $label): string => $label->trans($translator, $locale),
            $this->dimensionLabels,
        );

        return (new TranslatableMessage(
            'This summary cannot combine these dimensions: {dimensions}. Remove one or more dimensions or filters.',
            ['{dimensions}' => implode(', ', $labels)],
        ))->trans($translator, $locale);
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    /**
     * @return list<string>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    /**
     * @return list<list<string>>
     */
    public function getAvailableSets(): array
    {
        return $this->availableSets;
    }

    public function getAvailableSetsCount(): int
    {
        return $this->availableSetsCount;
    }
}
