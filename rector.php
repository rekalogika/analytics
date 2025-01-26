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

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector;
use Rector\Strict\Rector\Ternary\DisallowedShortTernaryRuleFixerRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPaths([
        __DIR__ . '/packages',
        __DIR__ . '/tests/bin',
        __DIR__ . '/tests/config',
        __DIR__ . '/tests/public',
        __DIR__ . '/tests/src',
    ])
    ->withImportNames(importShortClasses: false)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        strictBooleans: true,
        symfonyCodeQuality: true,
        doctrineCodeQuality: true,
    )
    ->withPhpSets(php82: true)
    ->withRules([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withSkip([
        // potential cognitive burden
        FlipTypeControlToUseExclusiveTypeRector::class,

        // results in too long variables
        CatchExceptionNameMatchingTypeRector::class,

        // makes code unreadable
        DisallowedShortTernaryRuleFixerRector::class,

        // makes code unreadable
        SimplifyIfElseToTernaryRector::class,

        CombineIfRector::class => [
            // don't fix symfony makerbundle boilerplate code
            __DIR__ . '/tests/src/App/Entity/*',
        ],

        BooleanInIfConditionRuleFixerRector::class => [
            __DIR__ . '/packages/analytics-core/src/SummaryManager/Query/QueryContext.php',
        ],

        RemoveConcatAutocastRector::class => [
            // psalm doesn't like it
            __DIR__ . '/packages/analytics-core/src/SummaryManager/Item.php',
        ],

        RemoveNonExistingVarAnnotationRector::class => [
            // psalm doesn't like it
            __DIR__ . '/packages/analytics-core/src/SummaryManager/DefaultSummaryManager.php',
        ],
    ]);
