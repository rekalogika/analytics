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

namespace Rekalogika\Analytics\Frontend\Formatter\Exception;

use Rekalogika\Analytics\Frontend\Formatter\Htmlifier;

final class HtmlifierFailureException extends FormatterFailureException
{
    public function __construct(mixed $input)
    {
        $message = \sprintf(
            'Unable to transform input value "%s" to HTML. To fix the problem, you need to create an implementation of "%s" for this type.',
            get_debug_type($input),
            Htmlifier::class,
        );

        parent::__construct($message);
    }
}
