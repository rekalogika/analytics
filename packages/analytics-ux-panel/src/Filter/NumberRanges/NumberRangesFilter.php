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

namespace Rekalogika\Analytics\UX\PanelBundle\Filter\NumberRanges;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\UX\PanelBundle\Filter;
use Symfony\Contracts\Translation\TranslatableInterface;

final class NumberRangesFilter implements Filter
{
    private readonly string $rawValue;

    private string $value = '';

    private ?Numbers $numbers = null;

    /**
     * @param array<string,mixed> $inputArray
     */
    public function __construct(
        private readonly NumberRangesFilterOptions $options,
        private readonly string $dimension,
        array $inputArray,
    ) {
        /** @psalm-suppress MixedAssignment */
        $rawValue = $inputArray['value'] ?? '';

        if (!\is_string($rawValue)) {
            $rawValue = '';
        }

        $this->rawValue = $rawValue;
    }

    #[\Override]
    public function getTemplate(): string
    {
        return '@RekalogikaAnalyticsUXPanel/filter/number_ranges_filter.html.twig';
    }

    #[\Override]
    public function getDimension(): string
    {
        return $this->dimension;
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->options->getLabel();
    }

    public function getHelp(): ?TranslatableInterface
    {
        return $this->options->getHelp();
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    public function getValue(): string
    {
        if ($this->value !== '') {
            return $this->value;
        }

        return $this->value = $this->getNumbers()->__toString();
    }

    public function getNumbers(): Numbers
    {
        if ($this->numbers !== null) {
            return $this->numbers;
        }

        return $this->numbers = Numbers::create(
            dimension: $this->dimension,
            options: $this->options,
            input: $this->rawValue,
        );
    }

    #[\Override]
    public function createExpression(): ?Expression
    {
        $expression = $this->getNumbers()->createExpression();

        return $expression;
    }
}
