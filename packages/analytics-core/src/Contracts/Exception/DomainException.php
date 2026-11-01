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
 * Thrown when a value is outside the domain defined by the framework, e.g.
 * when the metadata of a summary class is inconsistent.
 */
class DomainException extends \DomainException implements AnalyticsException {}
