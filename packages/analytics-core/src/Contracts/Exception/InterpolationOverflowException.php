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

use Rekalogika\Analytics\Contracts\Translation\NullTranslator;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Thrown when filling the gaps in a query result would create more cells than
 * the configured limit.
 *
 * When a dimension's members form a sequence, e.g. months, the framework
 * fills the result with the members missing between the ones present, so a
 * chart or a pivot table shows a continuous axis. A sparse result over a wide
 * range can require an excessive number of filled-in cells, which this
 * exception guards against.
 */
class InterpolationOverflowException extends OverflowException implements TranslatableInterface
{
    public function __construct(private int $limit)
    {
        parent::__construct($this->trans(new NullTranslator()));
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $translatable = new TranslatableMessage(
            'The limit of {limit} nodes has been reached when trying to fill gaps in the result. Please modify your query to return less records or use a narrower range.',
            [
                '{limit}' => $this->limit,
            ],
        );

        return $translatable->trans($translator, $locale);
    }

    /**
     * The configured maximum number of cells.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
