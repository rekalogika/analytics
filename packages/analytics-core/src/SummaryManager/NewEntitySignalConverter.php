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

namespace Rekalogika\Analytics\SummaryManager;

use Rekalogika\Analytics\Model\Entity\SummarySignal;

/**
 * If an entity with IDENTITY strategy is created, the framework doesn't know
 * the ID of the entity until it's persisted. Therefore, we create a 'new entity
 * signals' (these are SummarySignal entity without partition information)
 * during flush.
 *
 * Afterwards, we need to convert these signals to 'dirty partition signals'
 * (SummarySignal entity with partition information). This is done by this
 * service.
 */
final readonly class NewEntitySignalConverter
{
    public function __construct(
        private SummaryRefresherFactory $summaryRefresherFactory,
    ) {}

    /**
     * @param class-string $class
     * @return iterable<SummarySignal>
     */
    public function convertNewRecordsSignalsToDirtyPartitionSignals(
        string $class,
    ): iterable {
        $summaryRefresher = $this->summaryRefresherFactory
            ->createSummaryRefresher($class);

        $signals = $summaryRefresher
            ->convertNewRecordsSignalsToDirtyPartitionSignals();

        foreach ($signals as $signal) {
            yield $signal;
        }
    }

}
