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

use Rekalogika\Analytics\Tests\App\Kernel;

require_once __DIR__ . '/../../vendor/autoload_runtime.php';

return function (array $context) {
    \assert(\is_string($context['APP_ENV'] ?? null), 'APP_ENV is not defined');
    \assert(\is_string($context['APP_DEBUG'] ?? null), 'APP_DEBUG is not defined');
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
