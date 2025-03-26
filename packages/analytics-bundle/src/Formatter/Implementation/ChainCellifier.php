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

namespace Rekalogika\Analytics\Bundle\Formatter\Implementation;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Rekalogika\Analytics\Bundle\Formatter\BackendHtmlifier;
use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\Bundle\Formatter\Htmlifier;
use Rekalogika\Analytics\Bundle\Formatter\HtmlifierAware;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Bundle\Formatter\BackendCellifier;
use Rekalogika\Analytics\Bundle\Formatter\CellifierAware;
use Rekalogika\Analytics\Bundle\Formatter\CellProperties;

final readonly class ChainCellifier implements Cellifier
{
    /**
     * @var list<BackendCellifier>
     */
    private array $backendCellifier;

    /**
     * @param iterable<BackendCellifier> $backendCellifiers
     */
    public function __construct(
        iterable $backendCellifiers,
        private Stringifier $stringifier,
    ) {
        $newBackendCellifiers = [];

        foreach ($backendCellifiers as $backendCellifier) {
            if ($backendCellifier instanceof CellifierAware) {
                $backendCellifier = $backendCellifier->withCellifier($this);
            }

            $newBackendCellifiers[] = $backendCellifier;
        }

        $this->backendCellifier = $newBackendCellifiers;
    }

    #[\Override]
    public function toCell(mixed $input): CellProperties
    {
        foreach ($this->backendCellifier as $cellifier) {
            $result = $cellifier->toCell($input);

            if ($result !== null) {
                return $result;
            }
        }

        $result = $this->stringifier->toString($input);

        $content = htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

        return new CellProperties(
            content: $content,
            type: DataType::TYPE_STRING,
            formatCode: null,
            attributes: [],
        );
    }
}
