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

use Rekalogika\Analytics\Common\Exception\NotFoundException;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class SummaryClassRegistry
{
    public function __construct(
        private SummaryClassMapFactory $summaryClassMapFactory,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    /**
     * @return class-string
     */
    public function getClassFromHash(string $hash): string
    {
        $classMap = $this->summaryClassMapFactory->getClassMap();

        if (!isset($classMap[$hash])) {
            throw new NotFoundException('Summary class not found');
        }

        return $classMap[$hash];
    }

    /**
     * @return array<string,TranslatableInterface>
     */
    public function getHashToLabel(): array
    {
        $classes = $this->summaryClassMapFactory->getClassMap();
        $hashToLabel = [];

        foreach ($classes as $hash => $className) {
            $summaryMetadata = $this->summaryMetadataFactory
                ->getSummaryMetadata($className);

            $hashToLabel[$hash] = $summaryMetadata->getLabel();
        }

        return $hashToLabel;
    }
}
