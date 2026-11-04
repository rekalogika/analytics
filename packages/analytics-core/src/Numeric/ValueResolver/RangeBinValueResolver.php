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
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Numeric\RangeBin;
use Rekalogika\Analytics\Numeric\Util\NumberUtil;

/**
 * Bins a number into one of the cases of a `RangeBin` enum. The summary
 * property must be typed with the enum, and mapped as a column with the enum
 * as its `enumType`. A null input gives null.
 *
 * Example:
 *
 * ```php
 * #[ORM\Column(enumType: AgeGroup::class, nullable: true)]
 * #[Analytics\Dimension(
 *     source: new RangeBinValueResolver(
 *         binClass: AgeGroup::class,
 *         input: new PropertyValue('age'),
 *     ),
 *     label: new TranslatableMessage('Age group'),
 * )]
 * private ?AgeGroup $ageGroup = null;
 * ```
 */
final readonly class RangeBinValueResolver implements ValueResolver
{
    /**
     * @param class-string<RangeBin> $binClass
     */
    public function __construct(
        private string $binClass,
        private ValueResolver $input,
    ) {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!enum_exists($binClass) || !is_a($binClass, RangeBin::class, true)) {
            throw new InvalidArgumentException(\sprintf(
                'The class "%s" must be a backed enum implementing "%s".',
                $binClass,
                RangeBin::class,
            ));
        }

        $cases = $binClass::cases();
        $thresholds = $binClass::getThresholds();

        try {
            NumberUtil::assertThresholds($thresholds);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid thresholds of "%s": %s',
                $binClass,
                $e->getMessage(),
            ), previous: $e);
        }

        if (\count($thresholds) !== \count($cases) - 1) {
            throw new InvalidArgumentException(\sprintf(
                'The enum "%s" has %d cases, so it must have %d thresholds, got %d.',
                $binClass,
                \count($cases),
                \count($cases) - 1,
                \count($thresholds),
            ));
        }
    }

    /**
     * @return class-string<RangeBin>
     */
    public function getBinClass(): string
    {
        return $this->binClass;
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->input->getInvolvedProperties();
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        $binClass = $this->binClass;
        $thresholds = $binClass::getThresholds();

        // already validated in the constructor
        NumberUtil::assertThresholds($thresholds);

        $outputs = array_map(
            static fn(RangeBin $case): string => \is_int($case->value)
                ? (string) $case->value
                : "'" . str_replace("'", "''", $case->value) . "'",
            $binClass::cases(),
        );

        if ($outputs === []) {
            throw new InvalidArgumentException(\sprintf(
                'The enum "%s" has no cases.',
                $binClass,
            ));
        }

        return NumberUtil::getThresholdCaseExpression(
            input: $this->input->getExpression($context),
            thresholds: $thresholds,
            outputs: $outputs,
        );
    }
}
