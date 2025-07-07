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

namespace Rekalogika\Analytics\Common\Exception;

use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Common\Util\NullTranslator;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function getLimit(): int
    {
        return $this->limit;
    }
}
