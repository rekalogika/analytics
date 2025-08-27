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

use Symfony\Component\Dotenv\Dotenv;
use Rekalogika\Analytics\Tests\App\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

// Suppress deprecation warnings during tests
error_reporting(E_ALL & ~E_USER_DEPRECATED & ~E_DEPRECATED);

// Set up test environment variables if not already set
if (!isset($_SERVER['APP_ENV'])) {
    $_SERVER['APP_ENV'] = 'test';
}

if (!isset($_SERVER['KERNEL_CLASS'])) {
    $_SERVER['KERNEL_CLASS'] = Kernel::class;
}

// Ensure proper timezone is set for tests
if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0) {
    // Catch the specific deprecation and throw so we get a stack trace
    if (($severity & E_DEPRECATED) && str_contains($message, 'Implicit conversion from float-string')) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
    // Let everything else behave as usual
    return false; // allow default handler too
});
