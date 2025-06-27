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

namespace Rekalogika\Analytics\Tests\App\Analytics;

use Rekalogika\Analytics\Bundle\Formatter\BackendHtmlifier;
use Rekalogika\Analytics\Tests\App\Entity\Country;

final class CountryHtmlifier implements BackendHtmlifier
{
    #[\Override]
    public function toHtml(mixed $input): ?string
    {
        if (!$input instanceof Country) {
            return null;
        }

        $emoji = $this->countryCodeToEmojiFlag($input->getCode() ?? '');

        return \sprintf(
            '%s %s',
            $emoji,
            $input->getName() ?? '',
        );
    }

    private function countryCodeToEmojiFlag(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode); // Ensure uppercase
        $flag = '';

        foreach (str_split($countryCode) as $char) {
            /** @psalm-suppress PossiblyFalseOperand */
            $flag .= mb_convert_encoding('&#' . (127397 + \ord($char)) . ';', 'UTF-8', 'HTML-ENTITIES');
        }

        return $flag;
    }
}
