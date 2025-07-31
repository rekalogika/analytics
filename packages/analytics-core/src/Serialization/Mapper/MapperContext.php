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

namespace Rekalogika\Analytics\Serialization\Mapper;

use Rekalogika\Analytics\Contracts\Exception\LogicException;

final readonly class MapperContext
{
    /**
     * @param class-string $summaryClass The class name of the summary to be used.
     * @param string $currentField The current field being processed.
     */
    public function __construct(
        private string $summaryClass,
        private ?string $currentField = null,
    ) {}

    public function withCurrentField(string $currentField): self
    {
        return new self(
            summaryClass: $this->summaryClass,
            currentField: $currentField,
        );
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    public function getCurrentField(): string
    {
        if ($this->currentField === null) {
            throw new LogicException('Current field must be set in context.');
        }

        return $this->currentField;
    }
}
