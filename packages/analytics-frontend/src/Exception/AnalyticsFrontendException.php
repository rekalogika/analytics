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

namespace Rekalogika\Analytics\Frontend\Exception;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Exceptions thrown by the frontend framework. All error messages are
 * guaranteed to be user-friendly and not contain any technical details, and are
 * also translatable.
 */
interface AnalyticsFrontendException extends \Throwable, TranslatableInterface {}
