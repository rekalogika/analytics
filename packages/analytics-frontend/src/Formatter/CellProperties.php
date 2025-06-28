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

namespace Rekalogika\Analytics\Frontend\Formatter;

use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * CellProperties represents the properties of a cell in a spreadsheet. Will
 * be rendered as data-* attributes in HTML.
 *
 * @see PhpOffice\PhpSpreadsheet\Reader\Html
 *
 * @implements \IteratorAggregate<string,string>
 */
final readonly class CellProperties implements \IteratorAggregate
{
    /**
     * @param DataType::TYPE_* $type
     */
    public function __construct(
        private string $content = '',
        private string $type = DataType::TYPE_STRING,
        private ?string $formatCode = null,
    ) {}

    public function getHtmlAttributesAsString(): string
    {
        $attributes = [];

        foreach ($this->getIterator() as $key => $value) {
            $attributes[] = \sprintf(
                '%s="%s"',
                $key,
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE),
            );
        }

        return implode(' ', $attributes);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield 'data-type' => $this->type;

        if ($this->formatCode !== null) {
            yield 'data-format' => $this->formatCode;
        }
    }

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
}
