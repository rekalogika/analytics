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

class QueryResultOverflowException extends OverflowException implements TranslatableInterface
{
    public function __construct(private int $limit)
    {
        parent::__construct($this->trans(new NullTranslator()));
    }

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $translatable = new TranslatableMessage(
            'The query returns more than the safeguard limit of {limit} records. Modify your query to return less records.',
            ['{limit}' => $this->limit],
        );

        return $translatable->trans($translator, $locale);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
