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

namespace Rekalogika\Analytics\Bundle\Formatter;

use PhpOffice\PhpSpreadsheet\Cell\DataType;

final readonly class CellProperties
{
    /**
     * @param DataType::TYPE_* $type
     * @param array<string,string> $attributes Extra HTML attributes
     */
    public function __construct(
        private string $content = '',
        private string $type = DataType::TYPE_STRING,
        private ?string $formatCode = null,
        private array $attributes = [],
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFormatCode(): ?string
    {
        return $this->formatCode;
    }

    public function getAttributes(): string
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            $attributes[] = sprintf('%s="%s"', htmlspecialchars($key), htmlspecialchars($value));
        }

        return implode(' ', $attributes);
    }
}
