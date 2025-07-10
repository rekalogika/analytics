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

namespace Rekalogika\Analytics\Engine\SummaryManager\Component;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SourceIdRangeDeterminer;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

final readonly class SourceOfSummaryComponent
{
    /**
     * @var class-string $sourceClass
     */
    private string $sourceClass;

    public function __construct(
        private readonly SummaryMetadata $summaryMetadata,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->sourceClass = $summaryMetadata->getSourceClass();
    }

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    private function createRangeDeterminer(): SourceIdRangeDeterminer
    {
        return new SourceIdRangeDeterminer(
            class: $this->getSourceClass(),
            entityManager: $this->entityManager,
            summaryMetadata: $this->summaryMetadata,
        );
    }

    public function getHighestIdentifier(): int|string|null
    {
        return $this->createRangeDeterminer()->getMaxId();
    }

    public function getLowestIdentifier(): int|string|null
    {
        return $this->createRangeDeterminer()->getMinId();
    }
}
