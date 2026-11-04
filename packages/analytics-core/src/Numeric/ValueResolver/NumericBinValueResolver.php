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

namespace Rekalogika\Analytics\Numeric\ValueResolver;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Context\ValueTransformerContext;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Numeric\Bin\IndexedBin;
use Rekalogika\Analytics\Numeric\Binning;

/**
 * Bins a number using a `Binning` algorithm. The summary table stores the
 * index of the bin, so the summary property must be an integer column. In the
 * query result, the index is transformed into an `IndexedBin`. A null input
 * gives null.
 *
 * Example:
 *
 * ```php
 * #[ORM\Column(type: Types::SMALLINT, nullable: true)]
 * #[Analytics\Dimension(
 *     source: new NumericBinValueResolver(
 *         binning: new LinearBinning(width: 10, min: 26, max: 66),
 *         input: new PropertyValue('age'),
 *     ),
 *     label: new TranslatableMessage('Age'),
 * )]
 * private ?int $age = null;
 * ```
 *
 * @implements UserValueTransformer<int|numeric-string|IndexedBin,IndexedBin>
 */
final readonly class NumericBinValueResolver implements
    ValueResolver,
    UserValueTransformer
{
    public function __construct(
        private Binning $binning,
        private ValueResolver $input,
    ) {}

    public function getBinning(): Binning
    {
        return $this->binning;
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->input->getInvolvedProperties();
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        return $this->binning->getDQLExpression(
            $this->input->getExpression($context),
        );
    }

    #[\Override]
    public function getUserValue(
        mixed $rawValue,
        ValueTransformerContext $context,
    ): ?IndexedBin {
        if ($rawValue === null || $rawValue instanceof IndexedBin) {
            return $rawValue;
        }

        // some drivers return integers as strings
        if (\is_string($rawValue) && filter_var($rawValue, \FILTER_VALIDATE_INT) !== false) {
            $rawValue = (int) $rawValue;
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (!\is_int($rawValue)) {
            throw new InvalidArgumentException(\sprintf(
                'Expected integer, but got %s',
                get_debug_type($rawValue),
            ));
        }

        return IndexedBin::create($this->binning, $rawValue);
    }
}
