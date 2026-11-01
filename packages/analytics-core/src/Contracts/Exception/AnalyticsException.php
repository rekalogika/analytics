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

/**
 * Marker interface implemented by every exception thrown by the framework, so
 * that callers can catch all of them at once.
 */
interface AnalyticsException extends \Throwable {}
