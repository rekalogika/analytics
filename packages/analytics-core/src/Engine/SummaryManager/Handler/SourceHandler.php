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

namespace Rekalogika\Analytics\Engine\SummaryManager\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Metadata\Source\SourceMetadata;

/**
 * Represents a source class
 */
final readonly class SourceHandler
{
    public function __construct(
        private SourceMetadata $sourceMetadata,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceMetadata->getClass();
    }

    public function getManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }


}
