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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query\Expression;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

/**
 * Describes a field expression in DQL
 */
final readonly class FieldExpression
{
    public function __construct(
        private string $field,
        private int|string|ParameterType|ArrayParameterType|null $type,
    ) {}

    public function getField(): string
    {
        return $this->field;
    }

    public function getType(): int|string|ParameterType|ArrayParameterType|null
    {
        return $this->type;
    }
}
