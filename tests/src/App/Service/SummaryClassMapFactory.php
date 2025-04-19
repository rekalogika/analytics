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

namespace Rekalogika\Analytics\Tests\App\Service;

use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

final readonly class SummaryClassMapFactory
{
    public function __construct(
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    /**
     * @return array<string,class-string>
     */
    public function getClassMap(): array
    {
        $hashToClass = [];

        foreach ($this->summaryMetadataFactory->getSummaryClasses() as $className) {
            $hash = hash('xxh128', $className);

            $hashToClass[$hash] = $className;
        }

        return $hashToClass;
    }
}
