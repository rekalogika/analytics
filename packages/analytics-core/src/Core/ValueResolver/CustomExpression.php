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

namespace Rekalogika\Analytics\Core\ValueResolver;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;

final readonly class CustomExpression implements ValueResolver
{
    /**
     * @param non-empty-string $pattern
     */
    public function __construct(
        private string $expression,
        private string $pattern = '/\[\s*([a-zA-Z0-9_.*()\\\ ]+)\s*\]/',
    ) {}

    /**
     * Returns only the fields directly on the related entity
     *
     * @todo currently returns as is, should be improved to return normalized
     * values
     */
    #[\Override]
    public function getInvolvedProperties(): array
    {
        preg_match_all($this->pattern, $this->expression, $matches);

        return array_values(array_unique($matches[1]));
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        $callback = static function (array $matches) use ($context): string {
            $path = $matches[1]
                ?? throw new InvalidArgumentException('Invalid match format');

            if (!\is_string($path)) {
                throw new InvalidArgumentException('Match must be a string');
            }

            $path = trim($path);

            return $context->resolve($path);
        };

        $result = preg_replace_callback($this->pattern, $callback, $this->expression);

        if (null === $result) {
            throw new InvalidArgumentException('Invalid DQL format');
        }

        return $result;
    }
}
