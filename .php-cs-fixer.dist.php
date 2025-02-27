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

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/packages')
    ->in(__DIR__ . '/tests/src')
    ->in(__DIR__ . '/tests/config')
    ->in(__DIR__ . '/tests/public')
    ->append([
        __DIR__ . '/.php-cs-fixer.dist.php',
        __DIR__ . '/rector.php',
        __DIR__ . '/monorepo-builder.php',
    ]);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PER-CS2.0' => true,
    '@PER-CS2.0:risky' => true,
    'fully_qualified_strict_types' => true,
    'global_namespace_import' => [
        'import_classes' => false,
        'import_constants' => false,
        'import_functions' => false,
    ],
    'no_unneeded_import_alias' => true,
    'no_unused_imports' => true,
    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
        'imports_order' => ['class', 'function', 'const'],
    ],
    'declare_strict_types' => true,
    'native_function_invocation' => ['include' => ['@compiler_optimized']],
    'header_comment' => [
        'header' => <<<EOF
This file is part of rekalogika/analytics package.

(c) Priyadi Iman Nurcahyo <https://rekalogika.dev>

For the full copyright and license information, please view the LICENSE file
that was distributed with this source code.
EOF,
    ],
])
    ->setFinder($finder)
;
